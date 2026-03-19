<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DashboardController extends Controller
{
    protected $workflowService;
    protected string $storageDisk = 'r2';

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

        $oldStatus = $order->status->value;

        $order->update([
            'claimed_by' => null,
            'claimed_at' => null,
            'status' => OrderStatus::Pending,
        ]);

        \App\Models\OrderLog::create([
            'order_id'   => $order->id,
            'user_id'    => auth()->id(),
            'action'     => 'unclaim',
            'old_status' => $oldStatus,
            'new_status' => OrderStatus::Pending->value,
            'notes'      => 'Order returned to the pending pool',
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
        ], [
            'ai_report.required' => 'Please select the AI detection report PDF.',
            'ai_report.file' => 'AI report must be a valid file.',
            'ai_report.uploaded' => 'AI report failed to upload. Keep each report under 100MB and try again.',
            'ai_report.mimes' => 'AI report must be a PDF file.',
            'ai_report.max' => 'AI report must be 100MB or smaller.',

            'plag_report.required' => 'Please select the plagiarism report PDF.',
            'plag_report.file' => 'Plagiarism report must be a valid file.',
            'plag_report.uploaded' => 'Plagiarism report failed to upload. Keep each report under 100MB and try again.',
            'plag_report.mimes' => 'Plagiarism report must be a PDF file.',
            'plag_report.max' => 'Plagiarism report must be 100MB or smaller.',
        ]);

        $disk = $this->storageDisk;
        $aiPath = null;
        $plagPath = null;
        $reportPersisted = false;

        try {
            $aiPath = $request->file('ai_report')->store('reports/' . $order->id . '/ai', $disk);

            try {
                $plagPath = $request->file('plag_report')->store('reports/' . $order->id . '/plag', $disk);
            } catch (\Throwable $e) {
                if ($aiPath) {
                    Storage::disk($disk)->delete($aiPath);
                }
                throw $e;
            }

            if (!$aiPath || !$plagPath) {
                throw new \Exception('Failed to save the report files to storage. Please try again or contact support.');
            }

            $this->workflowService->uploadReport($order, auth()->user(), [
                'ai_report_path'   => $aiPath,
                'ai_report_disk'   => $disk,
                'plag_report_path' => $plagPath,
                'plag_report_disk' => $disk,
            ]);
            $reportPersisted = true;

            $freshOrder = $order->fresh();
            if ($freshOrder->status === OrderStatus::Pending) {
                $this->workflowService->startProcessing($freshOrder, auth()->user());
                $freshOrder->refresh();
            }

            $this->workflowService->deliver($freshOrder, auth()->user());

            if ($request->ajax()) {
                return response()->json([
                    'redirect' => route('dashboard'),
                    'success'  => 'Both reports uploaded. Order delivered successfully.',
                ]);
            }

            return redirect()->route('dashboard')->with('success', 'Both reports uploaded. Order delivered successfully.');
        } catch (\Throwable $e) {
            // If report metadata was not persisted, clean up uploaded blobs to avoid orphan files.
            if (!$reportPersisted) {
                if ($aiPath) {
                    try {
                        Storage::disk($disk)->delete($aiPath);
                    } catch (\Throwable $cleanupError) {
                        Log::warning('Failed to clean up AI report after upload failure.', [
                            'order_id' => $order->id,
                            'path' => $aiPath,
                            'disk' => $disk,
                            'message' => $cleanupError->getMessage(),
                        ]);
                    }
                }

                if ($plagPath) {
                    try {
                        Storage::disk($disk)->delete($plagPath);
                    } catch (\Throwable $cleanupError) {
                        Log::warning('Failed to clean up plagiarism report after upload failure.', [
                            'order_id' => $order->id,
                            'path' => $plagPath,
                            'disk' => $disk,
                            'message' => $cleanupError->getMessage(),
                        ]);
                    }
                }
            }

            Log::error('Vendor report upload failed.', [
                'order_id' => $order->id,
                'user_id' => auth()->id(),
                'disk' => $disk,
                'message' => $e->getMessage(),
                'exception' => get_class($e),
            ]);

            $message = $e->getMessage();
            if ($message === '' || str_contains(strtolower($message), 's3') || str_contains(strtolower($message), 'flysystem') || str_contains(strtolower($message), 'unable to write')) {
                $message = 'Report upload failed while saving files to storage. Please try again. If the issue continues, contact admin.';
            }

            if ($request->ajax()) {
                return response()->json(['error' => $message], 500);
            }

            return redirect()->route('dashboard')->with('error', $message);
        }
    }

    public function downloadFile(Order $order, \App\Models\OrderFile $file)
    {
        // Ensure the file belongs to this order
        if ($file->order_id !== $order->id) {
            abort(404);
        }

        // Only the vendor who claimed this order (or an admin) may download its files
        $user = auth()->user();
        if ($user->role !== 'admin' && (int) $order->claimed_by !== (int) $user->id) {
            abort(403);
        }

        $disk = $file->disk ?: $this->storageDisk;

        if (!Storage::disk($disk)->exists($file->file_path)) {
            return back()->with('error', 'File not found on storage. It may have been uploaded before the storage volume was attached. Please ask the client to re-upload.');
        }

        $stream = Storage::disk($disk)->readStream($file->file_path);

        return response()->streamDownload(function () use ($stream) {
            fpassthru($stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        }, basename($file->file_path));
    }
}
