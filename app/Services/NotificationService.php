<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Send Telegram notification to vendors when a new order is created
     */
    public function notifyVendorsNewOrder(Order $order): void
    {
        try {
            $botToken = config('services.telegram.bot_token');
            $chatId = config('services.telegram.vendor_chat_id');
            
            if (!$botToken || !$chatId) {
                Log::warning('Telegram credentials not configured. Skipping vendor notification.');
                return;
            }

            // Load relationships
            $order->load(['client', 'files']);

            // Build notification message
            $message = $this->buildOrderNotificationMessage($order);

            // Send to Telegram
            $response = Http::timeout(5)
                ->post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                    'chat_id' => $chatId,
                    'text' => $message,
                    'parse_mode' => 'Markdown',
                    'disable_web_page_preview' => true,
                ]);

            if ($response->successful()) {
                Log::info("Telegram notification sent for order #{$order->id}");
            } else {
                Log::error("Telegram API error: " . $response->body());
            }

        } catch (\Exception $e) {
            // Don't fail the order creation if notification fails
            Log::error("Failed to send Telegram notification for order #{$order->id}: {$e->getMessage()}");
        }
    }

    /**
     * Build the Telegram message for a new order
     */
    private function buildOrderNotificationMessage(Order $order): string
    {
        $clientName = $order->client->name ?? 'Unknown Client';
        $dueTime = $order->due_at ? $order->due_at->format('M d, Y H:i') : 'Not set';
        $trackingId = $order->token_view;
        
        // Base message
        $message = " *New Order Received!*\n\n";
        $message .= " *Order ID:* #{$order->id}\n";
        $message .= " *Tracking:* `{$trackingId}`\n";
        $message .= " *Client:* {$clientName}\n";
        $message .= " *Files:* {$order->files_count}\n";
        $message .= " *Due:* {$dueTime}\n";
        $message .= " *Source:* " . ucfirst($order->source) . "\n";
        
        // Add notes if present
        if ($order->notes) {
            $notes = mb_substr($order->notes, 0, 100); // Limit length
            $message .= " *Notes:* {$notes}\n";
        }
        
        // Add urgency indicator for high file count
        if ($order->files_count >= 10) {
            $message .= "\n *LARGE ORDER - High Priority!*\n";
        }
        
        // Add dashboard link
        $dashboardUrl = route('dashboard');
        $message .= "\n [Open Dashboard]({$dashboardUrl})";
        
        return $message;
    }

    /**
     * Send order status update notification (optional, for future use)
     */
    public function notifyOrderStatusChange(Order $order, string $oldStatus, string $newStatus): void
    {
        try {
            $botToken = config('services.telegram.bot_token');
            $chatId = config('services.telegram.vendor_chat_id');
            
            if (!$botToken || !$chatId) {
                return;
            }

            $order->load(['client', 'vendor']);

            $statusEmoji = match($newStatus) {
                'processing' => '',
                'delivered' => '',
                'cancelled' => '',
                default => ''
            };

            $message = "{$statusEmoji} *Order Status Updated*\n\n";
            $message .= " *Order:* #{$order->id}\n";
            $message .= " *Client:* {$order->client->name}\n";
            $message .= " *Status:* {$oldStatus}  *{$newStatus}*\n";
            
            if ($order->vendor) {
                $message .= " *Vendor:* {$order->vendor->name}\n";
            }

            Http::timeout(5)
                ->post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                    'chat_id' => $chatId,
                    'text' => $message,
                    'parse_mode' => 'Markdown',
                    'disable_web_page_preview' => true,
                ]);

        } catch (\Exception $e) {
            Log::error("Failed to send status update notification: {$e->getMessage()}");
        }
    }
}
