<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Exceptions\VendorReportStorageException;
use App\Exceptions\WorkflowException;
use App\Http\Requests\UploadVendorReportRequest;
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
        $this->storageDisk = $this->resolveStorageDisk();
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
        $currentOrder = $order->fresh();
        $orderToUse = $currentOrder ?: $order;

        $this->authorize('claim', $orderToUse);

        try {
            $this->workflowService->claim($orderToUse, auth()->user());
            $this->forgetVendorStatsCache(auth()->id());

            if ($request->expectsJson()) {
                $claimedOrder = Order::with(['client', 'files', 'report', 'vendor'])->find($orderToUse->id);
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
        $currentOrder = $order->fresh();
        $orderToUse = $currentOrder ?: $order;

        $this->authorize('unclaim', $orderToUse);
        $claimedBy = $orderToUse->claimed_by;

        try {
            // Delegate to the service so the order update and audit log are
            // always written atomically inside a single DB transaction.
            $this->workflowService->unclaim($orderToUse, auth()->user());
            $this->forgetVendorStatsCache(auth()->id(), $claimedBy);
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
        $currentOrder = $order->fresh();
        $orderToUse = $currentOrder ?: $order;

        try {
            $newStatus = OrderStatus::from($request->status);

            if ($newStatus === OrderStatus::Processing) {
                $this->authorize('process', $orderToUse);
                $this->workflowService->startProcessing($orderToUse, auth()->user());
            } elseif ($newStatus === OrderStatus::Delivered) {
                $this->authorize('deliver', $orderToUse);
                $this->workflowService->deliver($orderToUse, auth()->user());
            }

            $this->forgetVendorStatsCache(auth()->id(), $orderToUse->claimed_by);

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

    public function uploadReport(UploadVendorReportRequest $request, Order $order)
    {
        $currentOrder = $order->fresh(['report']);
        $orderToUse = $currentOrder ?: $order;

        // When the auto-release command has reclaimed an order (claimed_by is null),
        // the policy would return a generic 403. Surface a specific, helpful message instead.
        if (is_null($orderToUse->claimed_by) && auth()->user()->role !== 'admin') {
            $message = 'This order was released back to the available pool because the claim window expired. You can re-claim it from the Available Queue.';
            if ($request->expectsJson()) {
                return response()->json(['error' => $message], 403);
            }
            return redirect()->route('dashboard')->with('error', $message);
        }

        try {
            $this->authorize('uploadReport', $orderToUse);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'You are not authorized to upload reports for this order.'], 403);
            }
            return redirect()->route('dashboard')->with('error', 'You are not authorized to upload reports for this order.');
        }

        try {
            $this->uploadReportService->execute(
                $orderToUse,
                auth()->user(),
                $request->file('ai_report'),
                $request->file('plag_report'),
                $request->boolean('ai_skipped') ? $request->input('ai_skip_reason') : null,
            );
        } catch (VendorReportStorageException $e) {
            $message = 'Report upload failed while saving files to storage. Please try again. If the issue continues, contact admin.';

            if ($request->expectsJson()) {
                return response()->json(['error' => $message], 500);
            }

            return redirect()->route('dashboard')->with('error', $message);
        } catch (WorkflowException $e) {
            $message = trim($e->getMessage()) ?: 'Unable to submit reports for this order.';

            if ($request->expectsJson()) {
                return response()->json(['error' => $message], 409);
            }

            return redirect()->route('dashboard')->with('error', $message);
        } catch (\Throwable $e) {
            $message = 'Unexpected server error while saving reports. Please try again in a moment.';

            if ($request->expectsJson()) {
                return response()->json(['error' => $message], 500);
            }

            return redirect()->route('dashboard')->with('error', $message);
        }

        $this->forgetVendorStatsCache(auth()->id(), $orderToUse->claimed_by);
        $successMessage = 'Reports uploaded. Order delivered successfully.';

        if ($request->expectsJson()) {
            return response()->json([
                'redirect' => route('dashboard'),
                'success'  => $successMessage,
            ]);
        }

        return redirect()->route('dashboard')->with('success', $successMessage);
    }

    public function downloadFile(Order $order, \App\Models\OrderFile $file)
    {
        $currentOrder = $order->fresh();
        $orderToUse = $currentOrder ?: $order;

        // Ensure the file belongs to this order
        if ($file->order_id !== $orderToUse->id) {
            abort(404);
        }

        // Only the vendor who claimed this order (or an admin) may download its files
        $user = auth()->user();
        if ($user->role !== 'admin' && (int) $orderToUse->claimed_by !== (int) $user->id) {
            abort(403);
        }

        $disk = $file->disk ?: $this->storageDisk;

        if (!Storage::disk($disk)->exists($file->file_path)) {
            return back()->with('error', 'File not found on storage. It may have been uploaded before the storage volume was attached. Please ask the client to re-upload.');
        }

        // Auto-advance order to Processing when vendor downloads the file.
        if ($orderToUse->status === OrderStatus::Claimed) {
            try {
                app(\App\Services\OrderWorkflowService::class)
                    ->startProcessing($orderToUse, auth()->user());
            } catch (\Throwable $e) {
                // Non-fatal — download still proceeds even if status update fails
                report($e);
            }
        }

        $downloadName = $file->original_name ?: basename($file->file_path);

        return Storage::disk($disk)->download($file->file_path, $downloadName);
    }

    protected function forgetVendorStatsCache(?int ...$userIds): void
    {
        foreach (array_unique(array_filter($userIds)) as $userId) {
            Cache::forget('vendor_stats_' . $userId);
        }
    }

    protected function resolveStorageDisk(): string
    {
        $disk = config('filesystems.default');

        if (is_string($disk) && $disk !== '') {
            return $disk;
        }

        return app()->environment('production') ? 'r2' : 'local';
    }
}
