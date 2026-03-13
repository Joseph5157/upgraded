<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    public function notifyVendorsNewOrder(Order $order): void
    {
        try {
            $botToken = config('services.telegram.bot_token');
            $chatId = config('services.telegram.vendor_chat_id');
            
            if (!$botToken || !$chatId) {
                Log::warning('Telegram credentials not configured');
               return;
            }

            $order->load(['client', 'files']);
            $message = $this->buildOrderNotificationMessage($order);

            $response = Http::timeout(5)
                ->post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                    'chat_id' => $chatId,
                    'text' => $message,
                    'parse_mode' => 'Markdown',
                    'disable_web_page_preview' => true,
                ]);

            if ($response->successful()) {
                Log::info("Telegram sent for order #{$order->id}");
            }

        } catch (\Exception $e) {
            Log::error("Telegram failed for order #{$order->id}: {$e->getMessage()}");
        }
    }

    private function buildOrderNotificationMessage(Order $order): string
    {
        $clientName = $order->client->name ?? 'Unknown';
        $dueTime = $order->due_at ? $order->due_at->format('M d, Y H:i') : 'Not set';
        $trackingId = $order->token_view;
        
        $message = "New Order Received!\n\n";
        $message .= "Order ID: #{$order->id}\n";
        $message .= "Tracking: {$trackingId}\n";
        $message .= "Client: {$clientName}\n";
        $message .= "Files: {$order->files_count}\n";
        $message .= "Due: {$dueTime}\n";
        $message .= "Source: " . ucfirst($order->source) . "\n";
        
        if ($order->notes) {
            $notes = mb_substr($order->notes, 0, 100);
            $message .= "Notes: {$notes}\n";
        }
        
        if ($order->files_count >= 10) {
            $message .= "\nLARGE ORDER - High Priority!\n";
        }
        
        $dashboardUrl = route('dashboard');
        $message .= "\nDashboard: {$dashboardUrl}";
        
        return $message;
    }
}
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

        if (empty($data['ai_report_path'])) {
            throw new Exception("The AI report PDF file is required.");
        }

        if (empty($data['plag_report_path'])) {
            throw new Exception("The Plagiarism report PDF file is required.");
        }

        DB::transaction(function () use ($order, $user, $data) {
            OrderReport::updateOrCreate(
                ['order_id' => $order->id],
                [
                    'ai_report_path'   => $data['ai_report_path'],
                    'plag_report_path' => $data['plag_report_path'],
                ]
            );

            $this->logActivity($order, $user, 'upload_report', 'AI and Plagiarism report PDFs uploaded');
        });
    }

    /**
     * Mark an order as delivered.
     */
    public function deliver(Order $order, User $user): void
    {
        $this->assertVendorOrAdmin($order, $user, 'deliver');

        if ($order->status === OrderStatus::Delivered) {
            throw new Exception("This order has already been delivered and cannot be re-delivered.");
        }

        if ($order->status !== OrderStatus::Processing) {
            throw new Exception("Order must be in 'processing' status before delivery. Current status: '{$order->status->value}'.");
        }

        $report = $order->report()->first();
        if (!$report || !$report->ai_report_path || !$report->plag_report_path) {
            throw new Exception("Both the AI report and Plagiarism report PDFs must be uploaded before delivery.");
        }

        DB::transaction(function () use ($order, $user) {
            $oldStatus = $order->status->value;
            $order->update([
                'status'       => OrderStatus::Delivered,
                'delivered_at' => now(),
            ]);

            // Permanently record this delivery on the vendor's profile.
            // This count is NEVER decremented — even if client deletes the order later.
            $user->increment('delivered_orders_count');

            $this->logActivity($order, $user, 'deliver', 'Order delivered to client', $oldStatus, OrderStatus::Delivered->value);
        });
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
