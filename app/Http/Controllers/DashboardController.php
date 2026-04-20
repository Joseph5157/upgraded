<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\OrderWorkflowService;
use App\Services\UploadVendorReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class DashboardController extends Controller
{
    protected string $storageDisk;

    public function __construct(
        protected OrderWorkflowService $workflowService,
        protected UploadVendorReportService $uploadReportService,
    ) {
        $this->storageDisk = config('filesystems.default', 'r2');
    }

    public function index(Request $request)
    {
        $user = auth()->user();

        // Cache slower-moving stats for 60 seconds per user to reduce query load.
        $cachedStats = Cache::remember(
            'vendor_stats_' . $user->id,
            60,
            function () use ($user) {
                return [
                    'available_pool'      => Order::where('status', OrderStatus::Pending)
                        ->whereNull('claimed_by')
                        ->count(),

                    'active_jobs'         => Order::whereIn('status', [OrderStatus::Claimed, OrderStatus::Processing])
                        ->where('claimed_by', $user->id)
                        ->count(),

                    'total_checked_today' => Order::where('status', OrderStatus::Delivered)
                        ->where('claimed_by', $user->id)
                        ->whereDate('delivered_at', today())
                        ->count(),

                    'total_delivered'     => $user->delivered_orders_count ?? 0,
                ];
            }
        );

        $stats = $cachedStats;

        // Eager load relationships + consistent ordering
        $myWorkspace = Order::with(['client', 'files', 'report', 'vendor'])
            ->where('claimed_by', $user->id)
            ->whereIn('status', [OrderStatus::Pending, OrderStatus::Claimed, OrderStatus::Processing])
            ->latest()
            ->get();

        $availableFiles = Order::with(['client', 'files', 'vendor'])
            ->whereNull('claimed_by')
            ->where('status', OrderStatus::Pending)
            ->latest()
            ->take(50)
            ->get();

        if ($request->boolean('queue_only')) {
            return view('dashboard.partials.available-queue', compact('availableFiles'));
        }

        $recentHistory = Order::with(['client', 'files', 'report'])
            ->where('claimed_by', $user->id)
            ->where('status', OrderStatus::Delivered)
            ->latest('delivered_at')
            ->take(5)
            ->get();

        return view('dashboard', compact('stats', 'myWorkspace', 'availableFiles', 'recentHistory'));
    }

    public function claim(Request $request, Order $order)
    {
        $this->authorize('claim', $order);

        try {
            $this->workflowService->claim($order, auth()->user());

            if ($request->expectsJson()) {
                $claimedOrder = Order::with(['client', 'files', 'report', 'vendor'])->find($order->id);
                $rowHtml  = view('partials.workspace-row',  ['order' => $claimedOrder])->render();
                $cardHtml = view('partials.workspace-card', ['order' => $claimedOrder])->render();

                return response()->json([
                    'success'  => true,
                    'message'  => 'Order claimed.',
                    'rowHtml'  => $rowHtml,
                    'cardHtml' => $cardHtml,
                ]);
            }

            return back()->with('success', 'Order claimed and reserved in your workspace.');
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }
            return back()->with('error', $e->getMessage());
        }
    }

    public function unclaim(Request $request, Order $order)
    {
        $this->authorize('unclaim', $order);

        try {
            // Delegate to the service so the order update and audit log are
            // always written atomically inside a single DB transaction.
            $this->workflowService->unclaim($order, auth()->user());
            if ($request->expectsJson()) {
                return response()->json(['success' => true, 'message' => 'Order returned to pool.']);
            }
            return back()->with('success', 'Order returned to the available pool.');
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }
            return back()->with('error', $e->getMessage());
        }
    }

    public function updateStatus(Request $request, Order $order)
    {
        $request->validate(['status' => 'required|in:processing,delivered']);

        try {
            $newStatus = OrderStatus::from($request->status);

            if ($newStatus === OrderStatus::Processing) {
                $this->authorize('process', $order);
                $this->workflowService->startProcessing($order, auth()->user());
            } elseif ($newStatus === OrderStatus::Delivered) {
                $this->authorize('deliver', $order);
                $this->workflowService->deliver($order, auth()->user());
            }

            Cache::forget('vendor_stats_' . auth()->id());

            if ($request->expectsJson()) {
                return response()->json(['success' => true, 'message' => 'Status updated.']);
            }
            return back()->with('success', 'Status updated.');
        } catch (\Throwable $e) {
            if ($request->expectsJson()) {
                $status = $e instanceof \Illuminate\Auth\Access\AuthorizationException ? 403 : 422;
                $message = $e->getMessage() ?: 'Unable to update status.';
                return response()->json(['success' => false, 'message' => $message], $status);
            }

            return back()->with('error', $e->getMessage());
        }
    }

    public function uploadReport(Request $request, Order $order)
    {
        // When the auto-release command has reclaimed an order (claimed_by is null),
        // the policy would return a generic 403. Surface a specific, helpful message instead.
        if (is_null($order->claimed_by) && auth()->user()->role !== 'admin') {
            $message = 'This order was released back to the available pool because the deadline passed. You can re-claim it from the Available Queue.';
            if ($request->ajax()) {
                return response()->json(['error' => $message], 403);
            }
            return redirect()->route('dashboard')->with('error', $message);
        }

        $this->authorize('uploadReport', $order);

        $request->validate([
            'ai_skipped'     => 'sometimes|boolean',
            'ai_skip_reason' => 'nullable|required_if:ai_skipped,1|string|max:255',
            'ai_report'      => 'required_unless:ai_skipped,1|file|mimes:pdf|max:102400',
            'plag_report'    => 'required|file|mimes:pdf|max:102400',
        ], [
            'ai_report.required_unless' => 'Please select the AI detection report PDF or provide a reason for skipping it.',
            'ai_skip_reason.required_if' => 'Please explain why the AI report was unable to be generated.',
            'ai_report.file'       => 'AI report must be a valid file.',
            'ai_report.uploaded'   => 'AI report failed to upload. Keep each report under 100MB and try again.',
            'ai_report.mimes'      => 'AI report must be a PDF file.',
            'ai_report.max'        => 'AI report must be 100MB or smaller.',
            'plag_report.required' => 'Please select the plagiarism report PDF.',
            'plag_report.file'     => 'Plagiarism report must be a valid file.',
            'plag_report.uploaded' => 'Plagiarism report failed to upload. Keep each report under 100MB and try again.',
            'plag_report.mimes'    => 'Plagiarism report must be a PDF file.',
            'plag_report.max'      => 'Plagiarism report must be 100MB or smaller.',
        ]);

        try {
            $this->uploadReportService->execute(
                $order,
                auth()->user(),
                $request->file('ai_report'),
                $request->file('plag_report'),
                $request->input('ai_skipped') ? $request->input('ai_skip_reason') : null,
            );
        } catch (\Throwable $e) {
            $message = $e->getMessage();
            if ($message === '' || str_contains(strtolower($message), 's3') || str_contains(strtolower($message), 'flysystem') || str_contains(strtolower($message), 'unable to write')) {
                $message = 'Report upload failed while saving files to storage. Please try again. If the issue continues, contact admin.';
            }

            if ($request->ajax()) {
                return response()->json(['error' => $message], 500);
            }

            return redirect()->route('dashboard')->with('error', $message);
        }

        if ($request->ajax()) {
            return response()->json([
                'redirect' => route('dashboard'),
                'success'  => 'Both reports uploaded. Order delivered successfully.',
            ]);
        }

        return redirect()->route('dashboard')->with('success', 'Both reports uploaded. Order delivered successfully.');
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

        // Auto-advance order to Processing when vendor downloads the file.
        if ($order->status === OrderStatus::Claimed) {
            try {
                app(\App\Services\OrderWorkflowService::class)
                    ->startProcessing($order, auth()->user());
            } catch (\Throwable $e) {
                // Non-fatal — download still proceeds even if status update fails
                report($e);
            }
        }

        $downloadName = $file->original_name ?? basename($file->file_path);

        return Storage::disk($disk)->download($file->file_path, $downloadName);
    }
}
