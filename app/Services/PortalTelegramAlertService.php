<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class PortalTelegramAlertService
{
    public function __construct(
        protected TelegramService $telegramService,
    ) {}

    public function notifyOrderAccepted(Order $order, int $remainingCredits): void
    {
        $order->loadMissing(['client', 'creator', 'client.user']);

        // Vendor group broadcast
        $vendorGroupChatId = trim((string) config('services.telegram.vendor_chat_id'));
        if ($vendorGroupChatId !== '') {
            $vendorMessage = implode("\n", [
                'New file submission received.',
                "Order: #{$order->id}",
                'Client: ' . ($order->client?->name ?? 'Unknown Client'),
                "Files: {$order->files_count}",
                "Tracking: {$order->token_view}",
            ]);
            $sentToVendorGroup = $this->telegramService->sendMessage(
                $vendorGroupChatId,
                $vendorMessage,
                ['remove_keyboard' => true]
            );
            if (! $sentToVendorGroup) {
                Log::warning("Vendor Telegram alert failed for order #{$order->id}.", [
                    'vendor_chat_id' => $vendorGroupChatId,
                ]);
            }
        } else {
            Log::warning("Vendor Telegram alert skipped for order #{$order->id}: TELEGRAM_VENDOR_CHAT_ID not configured.");
        }

        // Client direct notification (if connected)
        $clientUser = $this->resolveClientRecipient($order);
        if ($clientUser?->telegram_chat_id) {
            $clientMessage = implode("\n", [
                'Your files were accepted successfully.',
                "Order ID: #{$order->id}",
                "Files: {$order->files_count}",
                "Tracking ID: {$order->token_view}",
                'Status: Pending review',
            ]);
            $this->telegramService->sendMessage((string) $clientUser->telegram_chat_id, $clientMessage);

            if ($remainingCredits <= 10) {
                $lowLimitMessage = implode("\n", [
                    'Low credit warning.',
                    "Remaining credits: {$remainingCredits}",
                    'Please top up to avoid upload interruption.',
                ]);
                $this->telegramService->sendMessage((string) $clientUser->telegram_chat_id, $lowLimitMessage);
            }
        } else {
            Log::info("Skipping client Telegram accepted alert for order #{$order->id}: client not connected.");
        }
    }

    public function notifyOrderCompleted(Order $order): void
    {
        $order->loadMissing(['client', 'creator', 'client.user']);
        $clientUser = $this->resolveClientRecipient($order);

        if (! $clientUser?->telegram_chat_id) {
            Log::info("Skipping client Telegram completion alert for order #{$order->id}: client not connected.");
            return;
        }

        $message = implode("\n", [
            '✅ *Your report is ready to download*',
            '',
            '*Order ID:* `#' . $order->id . '`',
            '*Tracking ID:* `' . $this->escapeMarkdown($order->token_view) . '`',
            '',
            '_Open your portal dashboard to download the files._',
        ]);

        $this->telegramService->sendMessage(
            (string) $clientUser->telegram_chat_id,
            $message,
            null,
            ['parse_mode' => 'Markdown']
        );
    }

    protected function escapeMarkdown(string $text): string
    {
        return str_replace(['_', '*', '[', '`'], ['\_', '\*', '\[', '\`'], $text);
    }

    protected function resolveClientRecipient(Order $order): ?User
    {
        if ($order->creator instanceof User) {
            return $order->creator;
        }

        return $order->client?->user;
    }
}
