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
                Log::warning('Telegram credentials not configured. Skipping vendor notification.');
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
                Log::info("Telegram notification sent for order #{$order->id}");
            } else {
                Log::error("Telegram API error: " . $response->body());
            }

        } catch (\Exception $e) {
            Log::error("Failed to send Telegram notification for order #{$order->id}: {$e->getMessage()}");
        }
    }

    private function buildOrderNotificationMessage(Order $order): string
    {
        $clientName = $order->client->name ?? 'Unknown Client';
        $dueTime = $order->due_at ? $order->due_at->format('M d, Y H:i') : 'Not set';
        $trackingId = $order->token_view;
        
        $message = " *New Order Received!*\n\n";
        $message .= " *Order ID:* #{$order->id}\n";
        $message .= " *Tracking:* `{$trackingId}`\n";
        $message .= " *Client:* {$clientName}\n";
        $message .= " *Files:* {$order->files_count}\n";
        $message .= " *Due:* {$dueTime}\n";
        $message .= " *Source:* " . ucfirst($order->source) . "\n";
        
        if ($order->notes) {
            $notes = mb_substr($order->notes, 0, 100);
            $message .= " *Notes:* {$notes}\n";
        }
        
        if ($order->files_count >= 10) {
            $message .= "\n *LARGE ORDER - High Priority!*\n";
        }
        
        $dashboardUrl = route('dashboard');
        $message .= "\n [Open Dashboard]({$dashboardUrl})";
        
        return $message;
    }

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