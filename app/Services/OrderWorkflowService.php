<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Exceptions\WorkflowException;
use App\Models\Order;
use App\Models\OrderLog;
use App\Models\OrderReport;
use App\Models\User;
use App\Support\LogContext;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderWorkflowService
{
    public function __construct(
        protected AuditLogger $auditLogger,
        protected \App\Services\NotificationService $notificationService,
    ) {
    }

    /**
     * Claim a pending, unclaimed order.
     *
     * All guards run INSIDE the transaction on a locked row so that two
     * concurrent requests can never both see the same unclaimed order and
     * both succeed — the second one will block on the row lock and then
     * discover claimed_by is already set.
     */
    public function claim(Order $order, User $user): void
    {
        DB::transaction(function () use ($order, $user) {
            // Lock the row for the duration of this transaction so concurrent
            // claim attempts are serialised at the database level.
            $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === OrderStatus::Cancelled) {
                throw new WorkflowException("This order has been cancelled by the client and is no longer available.");
            }

            if ($locked->status !== OrderStatus::Pending) {
                throw new WorkflowException("Only pending orders can be claimed. This order is '{$locked->status->value}'.");
            }

            if ($locked->claimed_by !== null) {
                throw new WorkflowException("This order has already been claimed and is not available.");
            }

            // Enforce a per-vendor cap so no single vendor monopolises the queue.
            $activeJobs = Order::where('claimed_by', $user->id)
                ->whereIn('status', [OrderStatus::Claimed, OrderStatus::Processing])
                ->count();

            if ($activeJobs >= 5) {
                throw new WorkflowException("You already have {$activeJobs} active job(s). Complete or release one before claiming another.");
            }

            $oldStatus = $locked->status->value;

            $locked->update([
                'claimed_by' => $user->id,
                'claimed_at' => now(),
                'status'     => OrderStatus::Claimed,
            ]);

            $this->logActivity(
                $locked,
                $user,
                'claim',
                'Order claimed and reserved for vendor',
                $oldStatus,
                OrderStatus::Claimed->value
            );

            $context = LogContext::forOrder($locked, LogContext::forUser($user, LogContext::currentRequest()));

            Log::info('order.claimed', $context);
            $this->auditLogger->record('order.claimed', $locked, [
                'old_status' => $oldStatus,
                'new_status' => OrderStatus::Claimed->value,
            ], $user->id);
        });

        // Claiming changes "available orders" and "active vendors" on the admin
        // dashboard — bust the cached snapshot so the next load is fresh.
        Cache::forget('admin_dashboard_stats');
    }

    /**
     * Return a claimed order to the unclaimed pending pool.
     *
     * The order update and audit log are written in a single transaction so
     * they can never diverge (previously the controller did both steps outside
     * any transaction and outside this service entirely).
     */
    public function unclaim(Order $order, User $user): void
    {
        $this->assertVendorOrAdmin($order, $user, 'unclaim');

        if (!in_array($order->status, [OrderStatus::Claimed, OrderStatus::Processing])) {
            throw new WorkflowException("Only reserved or in-progress orders can be released back to the queue.");
        }

        DB::transaction(function () use ($order, $user) {
            $oldStatus = $order->status->value;

            $order->update([
                'claimed_by' => null,
                'claimed_at' => null,
                'status'     => OrderStatus::Pending,
            ]);

            $this->logActivity(
                $order,
                $user,
                'unclaim',
                'Order returned to the pending pool',
                $oldStatus,
                OrderStatus::Pending->value
            );

            $context = LogContext::forOrder($order, LogContext::forUser($user, LogContext::currentRequest()));

            Log::info('order.unclaimed', $context);
            $this->auditLogger->record('order.unclaimed', $order, [
                'old_status' => $oldStatus,
                'new_status' => OrderStatus::Pending->value,
            ], $user->id);
        });
    }

    /**
     * Move a claimed order to processing.
     */
    public function startProcessing(Order $order, User $user): void
    {
        $this->assertVendorOrAdmin($order, $user, 'start processing');

        if ($order->status === OrderStatus::Cancelled) {
            throw new WorkflowException("This order has been cancelled by the client.");
        }

        if ($order->status === OrderStatus::Delivered) {
            throw new WorkflowException("Cannot change a delivered order back to processing.");
        }

        if ($order->status !== OrderStatus::Claimed) {
            throw new WorkflowException("Order must be in 'claimed' status to start processing. Current status: '{$order->status->value}'.");
        }

        DB::transaction(function () use ($order, $user) {
            $oldStatus = $order->status->value;
            // Reset claimed_at to now so the auto-release 2-hour window starts
            // from when work actually began, not from when the order was claimed.
            $order->update(['status' => OrderStatus::Processing, 'claimed_at' => now()]);
            $this->logActivity($order, $user, 'start_processing', 'Order processing started', $oldStatus, OrderStatus::Processing->value);

            $context = LogContext::forOrder($order, LogContext::forUser($user, LogContext::currentRequest()));

            Log::info('order.processing_started', $context);
            $this->auditLogger->record('order.processing_started', $order, [
                'old_status' => $oldStatus,
                'new_status' => OrderStatus::Processing->value,
            ], $user->id);
        });

        try {
            $this->notificationService->notifyOrderStatusChange(
                $order->fresh(),
                'pending',
                'processing'
            );
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * Attach a report to the order and update metrics.
     */
    public function uploadReport(Order $order, User $user, array $data): void
    {
        $this->assertVendorOrAdmin($order, $user, 'upload a report for');

        if ($order->status === OrderStatus::Cancelled) {
            throw new WorkflowException("Cannot upload a report for a cancelled order.");
        }

        if ($order->status === OrderStatus::Delivered) {
            throw new WorkflowException("Cannot upload a report for an order that has already been delivered.");
        }

        if (empty($data['ai_report_path']) && empty($data['ai_skip_reason'])) {
            throw new WorkflowException("The AI report PDF file is required, unless a valid reason for skipping is provided.");
        }

        if (empty($data['plag_report_path'])) {
            throw new WorkflowException("The Plagiarism report PDF file is required.");
        }

        DB::transaction(function () use ($order, $user, $data) {
            OrderReport::updateOrCreate(
                ['order_id' => $order->id],
                [
                    'ai_report_path'   => $data['ai_report_path'] ?? null,
                    'ai_report_disk'   => $data['ai_report_disk'] ?? 'r2',
                    'ai_skip_reason'   => $data['ai_skip_reason'] ?? null,
                    'plag_report_path' => $data['plag_report_path'],
                    'plag_report_disk' => $data['plag_report_disk'] ?? 'r2',
                ]
            );

            $this->logActivity($order, $user, 'upload_report', 'Report PDFs (and/or skip info) uploaded');
        });
    }

    /**
     * Mark an order as delivered.
     */
    public function deliver(Order $order, User $user): void
    {
        $this->assertVendorOrAdmin($order, $user, 'deliver');

        DB::transaction(function () use ($order, $user) {
            $lockedOrder = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($lockedOrder->status === OrderStatus::Delivered) {
                throw new WorkflowException("This order has already been delivered and cannot be re-delivered.");
            }

            if ($lockedOrder->status !== OrderStatus::Processing) {
                throw new WorkflowException("Order must be in 'processing' status before delivery. Current status: '{$lockedOrder->status->value}'.");
            }

            $report = $lockedOrder->report()->first();
            if (!$report || (empty($report->ai_report_path) && empty($report->ai_skip_reason)) || empty($report->plag_report_path)) {
                throw new WorkflowException("Both the AI report and Plagiarism report PDFs must be uploaded before delivery (or an AI skip reason provided).");
            }

            $oldStatus = $lockedOrder->status->value;
            $lockedOrder->update([
                'status'       => OrderStatus::Delivered,
                'delivered_at' => now(),
            ]);

            // Permanently record this delivery on the vendor's profile.
            // This count is NEVER decremented — even if client deletes the order later.
            $creditedVendor = $lockedOrder->claimed_by
                ? User::query()->lockForUpdate()->find($lockedOrder->claimed_by)
                : null;

            ($creditedVendor ?: $user)->increment('delivered_orders_count');
            ($creditedVendor ?: $user)->increment('daily_delivered_count');

            $this->logActivity($lockedOrder, $user, 'deliver', 'Order delivered to client', $oldStatus, OrderStatus::Delivered->value);
        });

        // Delivery changes the admin dashboard's "processed today" and
        // "active vendors" counts — bust the cached snapshot so the next
        // page load reflects the new order immediately.
        Cache::forget('admin_dashboard_stats');

        try {
            /** @var \App\Services\PortalTelegramAlertService $telegramAlerts */
            $telegramAlerts = app(\App\Services\PortalTelegramAlertService::class);
            $telegramAlerts->notifyOrderCompleted(Order::findOrFail($order->id));
        } catch (\Throwable $e) {
            report($e);
        }

        try {
            $this->notificationService->notifyOrderStatusChange(
                Order::findOrFail($order->id),
                'processing',
                'delivered'
            );
        } catch (\Throwable $e) {
            report($e);
        }
    }

    // ─── Private Helpers ────────────────────────────────────────────────────────

    protected function assertVendorOrAdmin(Order $order, User $user, string $action): void
    {
        $isOwner = (int) $order->claimed_by === (int) $user->id;
        $isAdmin = $user->role === 'admin';

        if (!$isOwner && !$isAdmin) {
            throw new WorkflowException("You are not authorized to {$action} this order.");
        }
    }

    protected function logActivity(
        Order $order,
        User $user,
        string $action,
        ?string $notes = null,
        ?string $oldStatus = null,
        ?string $newStatus = null
    ): void {
        OrderLog::create([
            'order_id'   => $order->id,
            'user_id'    => $user->id,
            'action'     => $action,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'notes'      => $notes,
        ]);
    }
}
