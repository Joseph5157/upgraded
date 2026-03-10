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
        // Vendor Dashboard Logic
        $stats = [
            'available_pool'       => Order::where('status', OrderStatus::Pending)->whereNull('claimed_by')->count(),
            'active_jobs'          => Order::where('status', OrderStatus::Processing)->where('claimed_by', $user->id)->count(),
            'total_checked_today'  => Order::where('status', OrderStatus::Delivered)
                ->where('claimed_by', $user->id)
                ->whereDate('delivered_at', today())
                ->count(),
            'overdue_count'        => Order::whereNotIn('status', [OrderStatus::Delivered, OrderStatus::Cancelled])
                ->where('due_at', '<', now())
                ->count(),
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
        if ($order->claimed_by !== auth()->id()) {
            return back()->with('error', 'You cannot release an order you have not claimed.');
        }

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
                $this->workflowService->startProcessing($order, auth()->user());
            } elseif ($request->status === OrderStatus::Delivered->value) {
                $this->workflowService->deliver($order, auth()->user());
            }

            return back()->with('success', 'Status updated.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function uploadReport(Request $request, Order $order)
    {
        $request->validate([
            'report' => 'required|file|mimes:pdf|max:102400', // 100MB PDF
            'ai_percentage' => 'nullable|numeric|min:0|max:100',
            'plag_percentage' => 'nullable|numeric|min:0|max:100',
        ]);

        try {
            $path = $request->file('report')->store('reports/' . $order->id);

            $this->workflowService->uploadReport($order, auth()->user(), [
                'report_path' => $path,
                'ai_percentage' => $request->ai_percentage,
                'plag_percentage' => $request->plag_percentage,
            ]);

            // Auto-promote to processing if still pending before delivering
            if ($order->fresh()->status === OrderStatus::Pending) {
                $this->workflowService->startProcessing($order->fresh(), auth()->user());
            }

            // Automatically deliver after upload
            $this->workflowService->deliver($order->fresh(), auth()->user());

            return back()->with('success', 'Report uploaded and order delivered successfully.');
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

        return Storage::download($file->file_path);
    }
}
