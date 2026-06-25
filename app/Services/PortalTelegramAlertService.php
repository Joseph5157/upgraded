<?php

namespace App\Services;

use App\Jobs\Telegram\SendTelegramMessageJob;
use App\Models\Client;
use App\Models\ClientPayment;
use App\Models\Order;
use App\Models\TelegramActionToken;
use App\Models\TopupRequest;
use App\Models\User;
use App\Services\Telegram\TelegramActionTokenService;
use App\Services\Telegram\TelegramMessageBuilder;
use Illuminate\Support\Facades\Log;

class PortalTelegramAlertService
{
    public function __construct(
        protected TelegramService $telegramService,
        protected TelegramMessageBuilder $messageBuilder,
        protected TelegramActionTokenService $tokenService,
    ) {}

    // ──────────────────────────────────────────────────────────────
    // Phase 1+2 — New queued notification methods
    // ──────────────────────────────────────────────────────────────

    /**
     * Notify client when their order is created (credits deducted).
     *
     * Sends via queued job. Includes a Cancel Request button for Phase 2 safe actions.
     */
    public function notifyOrderCreated(Order $order): void
    {
        $order->loadMissing(['client', 'creator', 'client.user']);

        $clientUser = $this->resolveClientRecipient($order);

        if (! $clientUser?->telegram_chat_id) {
            Log::info("notifyOrderCreated skipped for order #{$order->id}: client not connected.");
            return;
        }

        // Create a cancel request action token for the button
        $cancelToken = $this->tokenService->create(
            actionType:       TelegramActionToken::ACTION_ORDER_CANCEL_REQUEST,
            subject:          $order,
            createdForUserId: $clientUser->id,
            telegramUserId:   $clientUser->telegram_chat_id,
            requiredRole:     'client',
        );

        $message = $this->messageBuilder->orderCreatedForClient($order, $cancelToken);

        SendTelegramMessageJob::dispatch(
            chatId:      $clientUser->telegram_chat_id,
            message:     $message,
            subjectType: Order::class,
            subjectId:   $order->id,
            messageType: 'order.created',
        );

        // Also alert vendor group (plain text, no buttons)
        $this->alertVendorGroup($order);
    }

    /**
     * Notify client when their report is ready and approved.
     *
     * Generates a signed temporary download URL.
     */
    public function notifyReportReady(Order $order): void
    {
        $order->loadMissing(['client', 'creator', 'client.user']);
        $clientUser = $this->resolveClientRecipient($order);

        if (! $clientUser?->telegram_chat_id) {
            Log::info("notifyReportReady skipped for order #{$order->id}: client not connected.");
            return;
        }

        $downloadUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'client.reports.download',
            now()->addMinutes(config('telegram.download_link_ttl_minutes', 15)),
            ['order' => $order->id]
        );

        $message = $this->messageBuilder->reportReadyForClient($order, $downloadUrl);

        SendTelegramMessageJob::dispatch(
            chatId:      $clientUser->telegram_chat_id,
            message:     $message,
            subjectType: Order::class,
            subjectId:   $order->id,
            messageType: 'report.ready',
        );
    }

    /**
     * Notify admin when a client payment needs approval.
     *
     * Creates two-step confirmation action tokens for the Approve button.
     */
    public function notifyPaymentPending(ClientPayment $payment): void
    {
        $payment->loadMissing('client');

        $adminChatId = $this->resolveAdminChatId();
        if (! $adminChatId) {
            Log::warning("notifyPaymentPending skipped for payment #{$payment->id}: no admin chat ID.");
            return;
        }

        // Resolve the admin user for token ownership
        $adminUser = $this->resolveAdminUser();

        // Step 1 token: tapping "Approve Credits" sends confirmation
        $approveRequestToken = $this->tokenService->create(
            actionType:       TelegramActionToken::ACTION_PAYMENT_APPROVE_REQUEST,
            subject:          $payment,
            createdForUserId: $adminUser?->id,
            telegramUserId:   $adminChatId,
            requiredRole:     'admin',
        );

        $rejectToken = $this->tokenService->create(
            actionType:       TelegramActionToken::ACTION_PAYMENT_REJECT_REQUEST,
            subject:          $payment,
            createdForUserId: $adminUser?->id,
            telegramUserId:   $adminChatId,
            requiredRole:     'admin',
        );

        $message = $this->messageBuilder->paymentPending($payment, $approveRequestToken, $rejectToken);

        SendTelegramMessageJob::dispatch(
            chatId:      $adminChatId,
            message:     $message,
            subjectType: ClientPayment::class,
            subjectId:   $payment->id,
            messageType: 'payment.pending',
        );
    }

    /**
     * Notify client when their credits are added.
     */
    public function notifyCreditsAdded(Client $client, int $creditsAdded): void
    {
        $client->loadMissing('user');
        $clientUser = $client->user;

        if (! $clientUser?->telegram_chat_id) {
            return;
        }

        $message = $this->messageBuilder->creditsAdded($creditsAdded, (int) $client->credit_balance);

        SendTelegramMessageJob::dispatch(
            chatId:  $clientUser->telegram_chat_id,
            message: $message,
        );
    }

    /**
     * Notify a client when their credit balance falls at or below the threshold.
     *
     * Only sends once per threshold crossing — callers are responsible for
     * checking whether to send (e.g. only when balance just dropped to threshold).
     */
    public function notifyLowCredit(Client $client): void
    {
        $client->loadMissing('user');
        $clientUser = $client->user;

        if (! $clientUser?->telegram_chat_id) {
            return;
        }

        $message = $this->messageBuilder->lowCreditAlert((int) $client->credit_balance);

        SendTelegramMessageJob::dispatch(
            chatId:  $clientUser->telegram_chat_id,
            message: $message,
        );
    }

    /**
     * Notify a vendor when they are assigned an order.
     *
     * Creates Accept/Reject action tokens for the inline keyboard.
     */
    public function notifyVendorAssigned(Order $order, User $vendor): void
    {
        if (! $vendor->telegram_chat_id) {
            Log::info("notifyVendorAssigned skipped for order #{$order->id}: vendor {$vendor->id} not connected.");
            return;
        }

        $acceptToken = $this->tokenService->create(
            actionType:       TelegramActionToken::ACTION_VENDOR_ASSIGNMENT_ACCEPT,
            subject:          $order,
            createdForUserId: $vendor->id,
            telegramUserId:   $vendor->telegram_chat_id,
            requiredRole:     'vendor',
        );

        $rejectToken = $this->tokenService->create(
            actionType:       TelegramActionToken::ACTION_VENDOR_ASSIGNMENT_REJECT,
            subject:          $order,
            createdForUserId: $vendor->id,
            telegramUserId:   $vendor->telegram_chat_id,
            requiredRole:     'vendor',
        );

        $message = $this->messageBuilder->vendorAssigned($order, $acceptToken, $rejectToken);

        SendTelegramMessageJob::dispatch(
            chatId:      $vendor->telegram_chat_id,
            message:     $message,
            subjectType: Order::class,
            subjectId:   $order->id,
            messageType: 'vendor.assigned',
        );
    }

    /**
     * Notify admin when a vendor submits a report for review.
     */
    public function notifyVendorReportSubmitted(Order $order, User $vendor): void
    {
        $adminChatId = $this->resolveAdminChatId();
        if (! $adminChatId) {
            Log::warning("notifyVendorReportSubmitted skipped for order #{$order->id}: no admin chat ID.");
            return;
        }

        $adminUser = $this->resolveAdminUser();

        $approveRequestToken = $this->tokenService->create(
            actionType:       TelegramActionToken::ACTION_VENDOR_REPORT_APPROVE_REQUEST,
            subject:          $order,
            createdForUserId: $adminUser?->id,
            telegramUserId:   $adminChatId,
            requiredRole:     'admin',
        );

        $failToken = $this->tokenService->create(
            actionType:       TelegramActionToken::ACTION_VENDOR_REPORT_FAIL_REQUEST,
            subject:          $order,
            createdForUserId: $adminUser?->id,
            telegramUserId:   $adminChatId,
            requiredRole:     'admin',
        );

        $reworkToken = $this->tokenService->create(
            actionType:       TelegramActionToken::ACTION_VENDOR_REPORT_REWORK_REQUEST,
            subject:          $order,
            createdForUserId: $adminUser?->id,
            telegramUserId:   $adminChatId,
            requiredRole:     'admin',
        );

        $message = $this->messageBuilder->vendorReportSubmittedForAdmin($order, $vendor, $approveRequestToken, $failToken, $reworkToken);

        SendTelegramMessageJob::dispatch(
            chatId:      $adminChatId,
            message:     $message,
            subjectType: Order::class,
            subjectId:   $order->id,
            messageType: 'vendor.report.submitted',
        );
    }

    // ──────────────────────────────────────────────────────────────
    // Legacy methods (kept for backward compatibility)
    // ──────────────────────────────────────────────────────────────

    public function notifyOrderAccepted(Order $order, int $remainingCredits): void
    {
        $order->loadMissing(['client', 'creator', 'client.user']);

        $this->alertVendorGroup($order);

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

            $threshold = config('telegram.low_credit_threshold', 5);
            if ($remainingCredits <= $threshold) {
                $this->notifyLowCredit($order->client);
            }
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
            ['parse_mode' => 'Markdown']
        );
    }

    /** @deprecated Use notifyPaymentPending instead (Phase 1+2) */
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

        $envAdminChatId = $this->resolveAdminChatId();
        if ($envAdminChatId !== '') {
            $this->telegramService->sendMessage($envAdminChatId, $message);
            return;
        }

        $admins = User::where('role', 'admin')->whereNotNull('telegram_chat_id')->get();
        if ($admins->isEmpty()) {
            Log::warning('Topup submitted Telegram alert skipped: no admin chat configured.');
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

        $envAdminChatId = $this->resolveAdminChatId();
        if ($envAdminChatId !== '') {
            $this->telegramService->sendMessage($envAdminChatId, $message);
            return;
        }

        $admins = User::where('role', 'admin')->whereNotNull('telegram_chat_id')->get();
        if ($admins->isEmpty()) {
            Log::warning('Vendor payout request Telegram alert skipped: no admin chat configured.', ['vendor_id' => $vendor->id]);
            return;
        }
        foreach ($admins as $admin) {
            $this->telegramService->sendMessage((string) $admin->telegram_chat_id, $message);
        }
    }

    /** @deprecated Credits no longer come from TopupRequest approvals */
    public function notifyTopupApproved(TopupRequest $topupRequest): void
    {
        $topupRequest->loadMissing('client.user');
        $clientUser = $topupRequest->client?->user;

        if (! $clientUser?->telegram_chat_id) {
            Log::info("Topup approved Telegram alert skipped for request #{$topupRequest->id}: client not connected.");
            return;
        }

        $newBalance = (int) $topupRequest->client->fresh()->credit_balance;

        $message = implode("\n", [
            '✅ Top-Up Approved',
            '',
            "Your credits have been updated.",
            "New credit balance: {$newBalance}",
            '',
            'You can now upload documents from your dashboard.',
        ]);

        $this->telegramService->sendMessage((string) $clientUser->telegram_chat_id, $message);
    }

    // ──────────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────────

    protected function alertVendorGroup(Order $order): void
    {
        $vendorGroupChatId = trim((string) (config('telegram.vendor_chat_id') ?? config('services.telegram.vendor_chat_id')));
        if ($vendorGroupChatId === '') {
            Log::warning("Vendor Telegram alert skipped for order #{$order->id}: TELEGRAM_VENDOR_CHAT_ID not configured.");
            return;
        }

        $vendorMessage = implode("\n", [
            'New file submission received.',
            "Order: #{$order->id}",
            'Client: ' . ($order->client?->name ?? 'Unknown Client'),
            "Files: {$order->files_count}",
            "Tracking: {$order->token_view}",
        ]);

        SendTelegramMessageJob::dispatch(
            chatId:  $vendorGroupChatId,
            message: ['text' => $vendorMessage],
        );
    }

    protected function resolveAdminChatId(): string
    {
        return trim((string) (config('telegram.admin_chat_id') ?? config('services.telegram.admin_chat_id')));
    }

    protected function resolveAdminUser(): ?User
    {
        $chatId = $this->resolveAdminChatId();
        if ($chatId !== '') {
            return User::where('telegram_chat_id', $chatId)->where('role', 'admin')->first();
        }
        return User::where('role', 'admin')->whereNotNull('telegram_chat_id')->first();
    }

    protected function resolveClientRecipient(Order $order): ?User
    {
        if ($order->creator instanceof User) {
            return $order->creator;
        }
        return $order->client?->user;
    }

    protected function escapeMarkdown(string $text): string
    {
        return str_replace(['_', '*', '[', '`'], ['\_', '\*', '\[', '\`'], $text);
    }
}
