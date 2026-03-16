<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DashboardController extends Controller
{
    protected $workflowService;

    public function __construct(\App\Services\OrderWorkflowService $workflowService)
    {
        $this->workflowService = $workflowService;
    }

    public function index()
    {
        $user = auth()->user();

        // Auto-Release is now handled by the orders:auto-release scheduled command.
        $stats = [
            'available_pool'      => Order::where('status', OrderStatus::Pending)->whereNull('claimed_by')->count(),
            'active_jobs'         => Order::where('status', OrderStatus::Processing)->where('claimed_by', $user->id)->count(),
            'total_checked_today' => Order::where('status', OrderStatus::Delivered)
                ->where('claimed_by', $user->id)
                ->whereDate('delivered_at', today())
                ->count(),
            'overdue_count'       => Order::whereNotIn('status', [OrderStatus::Delivered, OrderStatus::Cancelled])
                ->where('due_at', '<', now())
                ->count(),
            'total_delivered'     => $user->delivered_orders_count,
        ];

        $myWorkspace = Order::with(['client', 'files', 'report', 'vendor'])
            ->where('claimed_by', $user->id)
            ->whereIn('status', [OrderStatus::Pending, OrderStatus::Processing])
            ->get();

        $availableFiles = Order::with(['client', 'files', 'vendor'])
            ->whereNull('claimed_by')
            ->where('status', OrderStatus::Pending)
            ->latest()
            ->get();

        $recentHistory = Order::with(['client', 'files', 'report'])
            ->where('claimed_by', $user->id)
            ->where('status', OrderStatus::Delivered)
            ->latest('delivered_at')
            ->take(5)
            ->get();

        return view('dashboard', compact('stats', 'myWorkspace', 'availableFiles', 'recentHistory'));
    }

    public function claim(Order $order)
    {
        $this->authorize('claim', $order);

        try {
            $this->workflowService->claim($order, auth()->user());
            $order->update(['claimed_at' => now()]);
            return back()->with('success', 'Order claimed successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function unclaim(Order $order)
    {
        $this->authorize('unclaim', $order);

        $order->update([
            'claimed_by' => null,
            'claimed_at' => null,
            'status' => OrderStatus::Pending,
        ]);

        return back()->with('success', 'Order returned to the available pool.');
    }

    public function updateStatus(Request $request, Order $order)
    {
        $request->validate(['status' => 'required|in:processing,delivered']);

        try {
            if ($request->status === OrderStatus::Processing->value) {
                $this->authorize('process', $order);
                $this->workflowService->startProcessing($order, auth()->user());
            } elseif ($request->status === OrderStatus::Delivered->value) {
                $this->authorize('deliver', $order);
                $this->workflowService->deliver($order, auth()->user());
            }

            return back()->with('success', 'Status updated.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function uploadReport(Request $request, Order $order)
    {
        $this->authorize('uploadReport', $order);

        $request->validate([
            'ai_report'   => 'required|file|mimes:pdf|max:102400',
            'plag_report' => 'required|file|mimes:pdf|max:102400',
        ]);

        try {
            $aiPath   = $request->file('ai_report')->store('reports/' . $order->id . '/ai');
            $plagPath = $request->file('plag_report')->store('reports/' . $order->id . '/plag');

            $this->workflowService->uploadReport($order, auth()->user(), [
                'ai_report_path'   => $aiPath,
                'plag_report_path' => $plagPath,
            ]);

            if ($order->fresh()->status === OrderStatus::Pending) {
                $this->workflowService->startProcessing($order->fresh(), auth()->user());
            }

            $this->workflowService->deliver($order->fresh(), auth()->user());

            return back()->with('success', 'Both reports uploaded. Order delivered successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function downloadFile(Order $order, \App\Models\OrderFile $file)
    {
        // Only vendors/admins can download order files
        if ($file->order_id !== $order->id) {
            abort(404);
        }

        if (!Storage::exists($file->file_path)) {
            return back()->with('error', 'File not found on storage. It may have been uploaded before the storage volume was attached. Please ask the client to re-upload.');
        }

        return Storage::download($file->file_path, basename($file->file_path));
    }
}
