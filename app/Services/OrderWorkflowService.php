<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderLog;
use App\Models\OrderReport;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;

class OrderWorkflowService
{
    /**
     * Claim a pending, unclaimed order.
     */
    public function claim(Order $order, User $user): void
    {
        if ($order->status === OrderStatus::Cancelled) {
            throw new Exception("This order has been cancelled by the client and is no longer available.");
        }

        if ($order->status !== OrderStatus::Pending) {
            throw new Exception("Only pending orders can be claimed. This order is '{$order->status->value}'.");
        }

        if ($order->claimed_by !== null) {
            throw new Exception("This order has already been claimed and is not available.");
        }

        DB::transaction(function () use ($order, $user) {
            $order->update(['claimed_by' => $user->id]);
            $this->logActivity($order, $user, 'claim', 'Order claimed by agent');
        });
    }

    /**
     * Move a pending order to processing.
     */
    public function startProcessing(Order $order, User $user): void
    {
        $this->assertVendorOrAdmin($order, $user, 'start processing');

        if ($order->status === OrderStatus::Cancelled) {
            throw new Exception("This order has been cancelled by the client.");
        }

        if ($order->status === OrderStatus::Delivered) {
            throw new Exception("Cannot change a delivered order back to processing.");
        }

        if ($order->status !== OrderStatus::Pending) {
            throw new Exception("Order must be in 'pending' status to start processing. Current status: '{$order->status->value}'.");
        }

        DB::transaction(function () use ($order, $user) {
            $oldStatus = $order->status->value;
            $order->update(['status' => OrderStatus::Processing]);
            $this->logActivity($order, $user, 'start_processing', 'Order processing started', $oldStatus, OrderStatus::Processing->value);
        });
    }

    /**
     * Attach a report to the order and update metrics.
     */
    public function uploadReport(Order $order, User $user, array $data): void
    {
        $this->assertVendorOrAdmin($order, $user, 'upload a report for');

        if ($order->status === OrderStatus::Cancelled) {
            throw new Exception("Cannot upload a report for a cancelled order.");
        }

        if ($order->status === OrderStatus::Delivered) {
            throw new Exception("Cannot upload a report for an order that has already been delivered.");
        }

        if (empty($data['ai_report_path']) && empty($data['ai_skip_reason'])) {
            throw new Exception("The AI report PDF file is required, unless a valid reason for skipping is provided.");
        }

        if (empty($data['plag_report_path'])) {
            throw new Exception("The Plagiarism report PDF file is required.");
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
                throw new Exception("This order has already been delivered and cannot be re-delivered.");
            }

            if ($lockedOrder->status !== OrderStatus::Processing) {
                throw new Exception("Order must be in 'processing' status before delivery. Current status: '{$lockedOrder->status->value}'.");
            }

            $report = $lockedOrder->report()->first();
            if (!$report || (empty($report->ai_report_path) && empty($report->ai_skip_reason)) || empty($report->plag_report_path)) {
                throw new Exception("Both the AI report and Plagiarism report PDFs must be uploaded before delivery (or an AI skip reason provided).");
            }

            $oldStatus = $lockedOrder->status->value;
            $lockedOrder->update([
                'status'       => OrderStatus::Delivered,
                'delivered_at' => now(),
            ]);

            // Permanently record this delivery on the vendor's profile.
            // This count is NEVER decremented — even if client deletes the order later.
            $user->increment('delivered_orders_count');

            $this->logActivity($lockedOrder, $user, 'deliver', 'Order delivered to client', $oldStatus, OrderStatus::Delivered->value);
        });

        try {
            /** @var \App\Services\PortalTelegramAlertService $telegramAlerts */
            $telegramAlerts = app(\App\Services\PortalTelegramAlertService::class);
            $telegramAlerts->notifyOrderCompleted(Order::findOrFail($order->id));
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
            throw new Exception("You are not authorized to {$action} this order.");
        }
    }

    protected function logActivity(
        Order $order,
        User $user,
        string $action,
        string $notes = null,
        string $oldStatus = null,
        string $newStatus = null
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
