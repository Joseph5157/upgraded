<?php

namespace App\Services;

use App\Models\Order;
use App\Models\TopupRequest;
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

    public function notifyTopupSubmitted(TopupRequest $topupRequest): void
    {
        $topupRequest->loadMissing('client');

        $message = implode("\n", [
            '💰 New Top-Up Request',
            '',
            'Client: ' . ($topupRequest->client->name ?? 'Unknown'),
            'Slots requested: ' . $topupRequest->amount_requested,
            'UTR / Txn ID: ' . $topupRequest->transaction_id,
            '',
            'Review it in the admin panel.',
        ]);

        // Primary: env-configured admin chat ID
        $envAdminChatId = trim((string) config('services.telegram.admin_chat_id'));
        if ($envAdminChatId !== '') {
            $this->telegramService->sendMessage($envAdminChatId, $message);
            return;
        }

        // Fallback: any admin with telegram_chat_id stored in DB
        $admins = User::where('role', 'admin')
            ->whereNotNull('telegram_chat_id')
            ->get();

        if ($admins->isEmpty()) {
            Log::warning('Topup submitted Telegram alert skipped: ADMIN_TELEGRAM_CHAT_ID not set and no admin has a linked Telegram in DB.');
            return;
        }

        foreach ($admins as $admin) {
            $this->telegramService->sendMessage((string) $admin->telegram_chat_id, $message);
        }
    }

    public function notifyVendorPayoutRequested(User $vendor, float $balance): void
    {
        $message = implode("\n", [
            '💸 Vendor Payout Request',
            '',
            'Vendor: ' . $vendor->name,
            'Amount requested: ₹' . number_format($balance, 0),
            '',
            'Review it in the admin panel → Finance → Payouts.',
        ]);

        $envAdminChatId = trim((string) config('services.telegram.admin_chat_id'));
        if ($envAdminChatId !== '') {
            $this->telegramService->sendMessage($envAdminChatId, $message);
            return;
        }

        $admins = User::where('role', 'admin')
            ->whereNotNull('telegram_chat_id')
            ->get();

        if ($admins->isEmpty()) {
            Log::warning('Vendor payout request Telegram alert skipped: ADMIN_TELEGRAM_CHAT_ID not set and no admin has a linked Telegram in DB.', [
                'vendor_id' => $vendor->id,
            ]);
            return;
        }

        foreach ($admins as $admin) {
            $this->telegramService->sendMessage((string) $admin->telegram_chat_id, $message);
        }
    }

    public function notifyTopupApproved(TopupRequest $topupRequest): void
    {
        $topupRequest->loadMissing('client.user');

        $clientUser = $topupRequest->client?->user;

        if (! $clientUser?->telegram_chat_id) {
            Log::info("Topup approved Telegram alert skipped for request #{$topupRequest->id}: client not connected.");
            return;
        }

        $newBalance = $topupRequest->client->fresh()->slots
                    - $topupRequest->client->fresh()->slots_consumed;

        $message = implode("\n", [
            '✅ Top-Up Approved',
            '',
            "Your top-up of {$topupRequest->amount_requested} slots has been approved.",
            "New credit balance: {$newBalance} slots",
            '',
            'You can now upload documents from your dashboard.',
        ]);

        $this->telegramService->sendMessage((string) $clientUser->telegram_chat_id, $message);
    }

    protected function resolveClientRecipient(Order $order): ?User
    {
        if ($order->creator instanceof User) {
            return $order->creator;
        }

        return $order->client?->user;
    }
}
