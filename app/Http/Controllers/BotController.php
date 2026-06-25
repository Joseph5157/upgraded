<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Order;
use App\Models\PendingInvite;
use App\Models\TelegramActionToken;
use App\Models\TelegramEventLog;
use App\Models\User;
use App\Services\Telegram\TelegramActionTokenService;
use App\Services\Telegram\TelegramPermissionService;
use App\Services\TelegramService;
use App\Support\LogContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BotController extends Controller
{
    public function __construct(
        private readonly TelegramService $telegram,
        private readonly TelegramActionTokenService $tokenService,
        private readonly TelegramPermissionService $permissionService,
    ) {}

    public function webhook(Request $request, string $secret): JsonResponse
    {
        $configuredSecret = (string) (config('telegram.webhook_secret') ?? config('services.telegram.webhook_secret'));
        if ($configuredSecret === '' || ! hash_equals($configuredSecret, $secret)) {
            abort(403);
        }

        // ── callback_query (inline keyboard button tap) ──────────────
        $callbackQuery = $request->input('callback_query');
        if (! empty($callbackQuery)) {
            return $this->handleCallbackQuery($callbackQuery);
        }

        // ── Regular message ──────────────────────────────────────────
        $message = $request->input('message', []);
        $text    = trim((string) data_get($message, 'text', ''));
        $chatId  = (string) data_get($message, 'chat.id', '');

        if ($chatId === '' || $text === '') {
            return response()->json(['ok' => true]);
        }

        return $this->handleTextMessage($chatId, $text);
    }

    // ──────────────────────────────────────────────────────────────
    // Callback query handling — 13-step action token flow
    // ──────────────────────────────────────────────────────────────

    private function handleCallbackQuery(array $cq): JsonResponse
    {
        $callbackQueryId = (string) data_get($cq, 'id', '');
        $telegramUserId  = (string) data_get($cq, 'from.id', '');
        $callbackData    = (string) data_get($cq, 'data', '');
        $chatId          = (string) data_get($cq, 'message.chat.id', $telegramUserId);
        $messageId       = (int) data_get($cq, 'message.message_id', 0);

        // Step 1 — parse token UUID from callback_data ("a:<uuid>")
        $tokenUuid = $this->tokenService->parseCallbackData($callbackData);

        if (! $tokenUuid) {
            $this->telegram->answerCallbackQuery($callbackQueryId, 'Unknown action.');
            return response()->json(['ok' => true]);
        }

        // Step 2 — look up token
        // Step 3 — check not expired / Step 4 — check not used
        $token = $this->tokenService->validate($tokenUuid);

        if (! $token) {
            $rawToken = TelegramActionToken::where('token', $tokenUuid)->first();
            $reason   = $rawToken?->isUsed() ? 'expired' : 'expired';
            $logStatus = $rawToken?->isUsed() ? TelegramEventLog::STATUS_EXPIRED : TelegramEventLog::STATUS_EXPIRED;

            $this->telegram->answerCallbackQuery($callbackQueryId, 'This button has expired or already been used.', true);

            $this->writeEventLog($telegramUserId, null, 'callback.token.' . ($rawToken?->status ?? 'not_found'), null, [
                'callback_data' => $callbackData,
            ], null, $logStatus);

            return response()->json(['ok' => true]);
        }

        // Step 5 — find linked portal user by Telegram user ID
        $user = $this->permissionService->resolveUser($telegramUserId);

        if (! $user) {
            $this->telegram->answerCallbackQuery(
                $callbackQueryId,
                'Your Telegram account is not linked to a portal account. Use /start to link.',
                true,
            );
            $this->writeEventLog($telegramUserId, null, 'callback.denied.unlinked', $token, [
                'action_type' => $token->action_type,
            ], null, TelegramEventLog::STATUS_DENIED);

            return response()->json(['ok' => true]);
        }

        // Step 6 — check role and permission
        if (! $this->permissionService->canRedeem($user, $token, $telegramUserId)) {
            $this->telegram->answerCallbackQuery($callbackQueryId, 'You do not have permission to perform this action.', true);
            $this->writeEventLog($telegramUserId, $user->id, 'callback.denied.permission', $token, [
                'action_type'   => $token->action_type,
                'user_role'     => $user->role,
                'required_role' => $token->required_role,
            ], null, TelegramEventLog::STATUS_DENIED);

            return response()->json(['ok' => true]);
        }

        // Step 7 — check subject still exists
        $subject = $token->subject_type ? $token->subject : null;

        // Step 8 — dispatch action
        return $this->dispatchCallbackAction($cq, $token, $user, $subject, $callbackQueryId, $chatId, $messageId, $telegramUserId);
    }

    /**
     * Execute the action associated with a validated action token.
     *
     * Steps 9–13: execute in DB transaction, mark used, answer callback,
     *              edit message, write audit log.
     */
    private function dispatchCallbackAction(
        array $cq,
        TelegramActionToken $token,
        User $user,
        mixed $subject,
        string $callbackQueryId,
        string $chatId,
        int $messageId,
        string $telegramUserId,
    ): JsonResponse {
        $actionType = $token->action_type;

        // ── Phase 2 — Safe / non-accounting actions ──────────────────

        if ($actionType === TelegramActionToken::ACTION_ORDER_VIEW) {
            $this->telegram->answerCallbackQuery($callbackQueryId, 'Opening order...');
            DB::transaction(function () use ($token, $user) {
                $this->tokenService->markUsed($token, $user->id);
            });
            $this->writeEventLog($telegramUserId, $user->id, 'callback.order.view', $token, [], null, TelegramEventLog::STATUS_SUCCESS);
            return response()->json(['ok' => true]);
        }

        if ($actionType === TelegramActionToken::ACTION_VENDOR_ASSIGNMENT_ACCEPT) {
            return $this->handleVendorAssignmentAccept($token, $user, $subject, $callbackQueryId, $chatId, $messageId, $telegramUserId);
        }

        if ($actionType === TelegramActionToken::ACTION_VENDOR_ASSIGNMENT_REJECT) {
            // Rejection requires a reason — send them to the portal
            $portalUrl = rtrim(config('app.url'), '/') . ($subject ? "/vendor/orders/{$subject->id}" : '/vendor/orders');
            $this->telegram->answerCallbackQuery($callbackQueryId, 'Please provide a reason in the portal.');
            $this->telegram->editMessageText($chatId, $messageId,
                "Please open the portal to reject this assignment with a reason:\n{$portalUrl}",
                ['inline_keyboard' => [[['text' => 'Open Portal', 'url' => $portalUrl]]]],
            );
            DB::transaction(function () use ($token, $user) {
                $this->tokenService->markUsed($token, $user->id);
            });
            $this->writeEventLog($telegramUserId, $user->id, 'callback.vendor.assignment.reject_redirect', $token, [], null, TelegramEventLog::STATUS_SUCCESS);
            return response()->json(['ok' => true]);
        }

        // ── Phase 3 — Sensitive accounting actions (prepared, not yet live) ──
        // To enable Phase 3, uncomment the relevant blocks here and wire up
        // the appropriate service calls (e.g. ClientPaymentService::approve).

        // if ($actionType === TelegramActionToken::ACTION_PAYMENT_APPROVE_REQUEST) { ... }
        // if ($actionType === TelegramActionToken::ACTION_PAYMENT_APPROVE_CONFIRM) { ... }
        // if ($actionType === TelegramActionToken::ACTION_PAYMENT_REJECT_REQUEST)  { ... }
        // if ($actionType === TelegramActionToken::ACTION_VENDOR_PAYOUT_PAID_CONFIRM) { ... }

        // ── Unknown action ────────────────────────────────────────────
        $this->telegram->answerCallbackQuery($callbackQueryId, 'This action is not yet enabled.');
        $this->writeEventLog($telegramUserId, $user->id, 'callback.unknown_action', $token, [
            'action_type' => $actionType,
        ], null, TelegramEventLog::STATUS_ERROR);

        return response()->json(['ok' => true]);
    }

    /**
     * Handle vendor accepting an assignment.
     *
     * Marks the order's assignment as accepted (sets accepted_at on the order
     * if the column exists). Uses a DB transaction with markUsed inside.
     */
    private function handleVendorAssignmentAccept(
        TelegramActionToken $token,
        User $user,
        mixed $subject,
        string $callbackQueryId,
        string $chatId,
        int $messageId,
        string $telegramUserId,
    ): JsonResponse {
        $order = $subject instanceof Order ? $subject : null;

        if (! $order) {
            $this->telegram->answerCallbackQuery($callbackQueryId, 'Order no longer exists.', true);
            $this->writeEventLog($telegramUserId, $user->id, 'callback.vendor.accept.subject_missing', $token, [], null, TelegramEventLog::STATUS_ERROR);
            return response()->json(['ok' => true]);
        }

        if ((int) $order->claimed_by !== (int) $user->id) {
            $this->telegram->answerCallbackQuery($callbackQueryId, 'This order is not assigned to you.', true);
            $this->writeEventLog($telegramUserId, $user->id, 'callback.vendor.accept.wrong_vendor', $token, [
                'order_id'       => $order->id,
                'assigned_to'    => $order->claimed_by,
                'requesting_uid' => $user->id,
            ], null, TelegramEventLog::STATUS_DENIED);
            return response()->json(['ok' => true]);
        }

        DB::transaction(function () use ($token, $user, $order) {
            // Mark assignment accepted if the column exists
            if (array_key_exists('accepted_at', $order->getAttributes()) || $order->getConnection()->getSchemaBuilder()->hasColumn('orders', 'accepted_at')) {
                $order->forceFill(['accepted_at' => now()])->save();
            }

            $this->tokenService->markUsed($token, $user->id);
        });

        $this->telegram->answerCallbackQuery($callbackQueryId, 'Work accepted!');
        $this->telegram->editMessageText(
            $chatId,
            $messageId,
            "<b>Work Accepted</b>\nOrder: <code>{$order->order_number}</code>\nStatus: Accepted by {$user->name}",
            ['inline_keyboard' => [[
                ['text' => 'Open Upload Page', 'url' => rtrim(config('app.url'), '/') . "/vendor/orders/{$order->id}"],
            ]]],
        );

        $this->writeEventLog($telegramUserId, $user->id, 'callback.vendor.assignment.accepted', $token, [
            'order_id' => $order->id,
        ], ['accepted' => true], TelegramEventLog::STATUS_SUCCESS);

        return response()->json(['ok' => true]);
    }

    // ──────────────────────────────────────────────────────────────
    // Text message handler
    // ──────────────────────────────────────────────────────────────

    private function handleTextMessage(string $chatId, string $text): JsonResponse
    {
        if ($text === '/login') {
            return $this->handleLogin($chatId);
        }

        if ($text === '/myid') {
            return $this->handleMyId($chatId);
        }

        if ($text === '/help') {
            return $this->handleHelp($chatId);
        }

        if ($text === '/status') {
            return $this->handleStatus($chatId);
        }

        if ($text === '/credits' || $text === '/balance') {
            return $this->handleCredits($chatId);
        }

        if ($text === '/jobs') {
            return $this->handleJobs($chatId);
        }

        if ($text === '/earnings') {
            return $this->handleEarnings($chatId);
        }

        if ($text === '/stats') {
            return $this->handleStats($chatId);
        }

        if ($text === '/pending') {
            return $this->handlePending($chatId);
        }

        if ($text === '/cleartoday') {
            return $this->handleClearToday($chatId);
        }

        if ($text === '/unlink') {
            return $this->handleUnlink($chatId);
        }

        if (str_starts_with($text, '/start')) {
            return $this->handleStart($chatId, $text);
        }

        // Unknown command
        if (str_starts_with($text, '/')) {
            $linked = User::where('telegram_chat_id', $chatId)->exists();
            if ($linked) {
                $this->telegram->sendMessage($chatId, 'Unknown command. Type /help to see available commands.');
            }
        }

        return response()->json(['ok' => true]);
    }

    // ──────────────────────────────────────────────────────────────
    // Command handlers
    // ──────────────────────────────────────────────────────────────

    private function handleLogin(string $chatId): JsonResponse
    {
        $user = User::where('telegram_chat_id', $chatId)
            ->whereNotNull('activated_at')
            ->whereNull('deleted_at')
            ->first();

        if (! $user) {
            $this->telegram->sendMessage($chatId, 'No active account found for this Telegram. Contact your admin.');
            return response()->json(['ok' => true]);
        }

        if ($user->isFrozen()) {
            $this->telegram->sendMessage($chatId, 'Your account is frozen. Contact your admin.');
            return response()->json(['ok' => true]);
        }

        $token = Str::random(48);
        $user->forceFill([
            'login_token'            => $token,
            'login_token_expires_at' => now()->addMinutes(5),
        ])->save();

        $loginUrl = rtrim(config('app.url'), '/') . '/auth/telegram/' . $token;
        $sent = $this->telegram->sendMessage($chatId, "Tap to login (expires in 5 minutes):\n{$loginUrl}");

        if (! $sent) {
            $user->forceFill([
                'login_token'            => null,
                'login_token_expires_at' => null,
            ])->save();

            Log::warning('telegram.login_token.delivery_failed', array_merge(
                LogContext::currentRequest(),
                ['user_id' => $user->id, 'chat_id' => $chatId]
            ));
        } else {
            Log::info('telegram.login_token.issued', array_merge(
                LogContext::currentRequest(),
                LogContext::forUser($user, ['chat_id' => $chatId, 'token_length' => strlen($token)])
            ));
        }

        return response()->json(['ok' => true]);
    }

    private function handleMyId(string $chatId): JsonResponse
    {
        $user = User::where('telegram_chat_id', $chatId)
            ->whereNotNull('portal_number')
            ->first();

        if (! $user) {
            $this->telegram->sendMessage($chatId, 'No portal account is linked to this Telegram. Contact your admin.');
            return response()->json(['ok' => true]);
        }

        $this->telegram->sendMessage(
            $chatId,
            "Your Portal ID is: {$user->portal_number}\n\nUse it to log in at " . rtrim(config('app.url'), '/') . '/login'
        );

        return response()->json(['ok' => true]);
    }

    private function handleHelp(string $chatId): JsonResponse
    {
        $user = User::where('telegram_chat_id', $chatId)->first();
        $role = $user?->role;

        if ($role === 'vendor') {
            $helpText = implode("\n", [
                '👋 *Vendor Commands*',
                '',
                '/login — Get a portal login link',
                '/myid — See your Portal ID',
                '/jobs — View your active jobs',
                '/earnings — See your earnings summary',
                '/unlink — Unlink your Telegram account',
                '/help — Show this message',
            ]);
        } elseif ($role === 'client') {
            $helpText = implode("\n", [
                '👋 *Client Commands*',
                '',
                '/login — Get a portal login link',
                '/myid — See your Portal ID',
                '/status — View your active orders',
                '/credits — Check your credit balance',
                '/unlink — Unlink your Telegram account',
                '/help — Show this message',
            ]);
        } elseif ($role === 'admin') {
            $helpText = implode("\n", [
                '👋 *Admin Commands*',
                '',
                '/login — Get a portal login link',
                '/myid — See your Portal ID',
                '/stats — Live portal snapshot',
                '/pending — Pending payments/approvals',
                '/unlink — Unlink your Telegram account',
                '/help — Show this message',
            ]);
        } else {
            $helpText = implode("\n", [
                '👋 *Welcome to the Portal Bot*',
                '',
                '/login — Log into the portal',
                '/myid — See your Portal ID',
                '/help — Show this message',
                '',
                'If you have an invite link, tap it to activate your account.',
            ]);
        }

        $this->telegram->sendMessage($chatId, $helpText, ['parse_mode' => 'Markdown']);
        return response()->json(['ok' => true]);
    }

    private function handleStatus(string $chatId): JsonResponse
    {
        $user = User::where('telegram_chat_id', $chatId)->whereNotNull('activated_at')->first();

        if (! $user || $user->role !== 'client' || ! $user->client) {
            $this->telegram->sendMessage($chatId, 'This command is only available for client accounts.');
            return response()->json(['ok' => true]);
        }

        $client       = $user->client;
        $activeOrders = Order::where('client_id', $client->id)
            ->whereIn('status', ['pending', 'claimed', 'processing'])
            ->latest()
            ->take(5)
            ->get();

        $delivered = Order::where('client_id', $client->id)
            ->where('status', 'delivered')
            ->whereDate('delivered_at', today())
            ->count();

        if ($activeOrders->isEmpty()) {
            $body = "No active orders right now.";
            if ($delivered > 0) {
                $body .= "\n\n✅ {$delivered} order(s) delivered today.";
            }
        } else {
            $lines = ["📋 *Your Active Orders*", ''];
            foreach ($activeOrders as $order) {
                $statusLabel = match ($order->status->value) {
                    'pending'    => '⏳ Pending',
                    'claimed'    => '🔒 Reserved',
                    'processing' => '⚙️ Processing',
                    default      => ucfirst($order->status->value),
                };
                $lines[] = "#{$order->token_view} — {$statusLabel}";
            }
            $lines[] = '';
            // Use credit_balance (finance ledger) instead of legacy slots columns
            $remaining = max(0, (int) $client->credit_balance);
            $lines[]   = "💳 Credits remaining: {$remaining}";
            $body = implode("\n", $lines);
        }

        $this->telegram->sendMessage($chatId, $body, ['parse_mode' => 'Markdown']);
        return response()->json(['ok' => true]);
    }

    private function handleCredits(string $chatId): JsonResponse
    {
        $user = User::where('telegram_chat_id', $chatId)->whereNotNull('activated_at')->first();

        if (! $user || $user->role !== 'client' || ! $user->client) {
            $this->telegram->sendMessage($chatId, 'This command is only available for client accounts.');
            return response()->json(['ok' => true]);
        }

        $client    = $user->client;
        // Use credit_balance (finance ledger) — the single source of truth
        $remaining = max(0, (int) $client->credit_balance);
        $threshold = config('telegram.low_credit_threshold', 5);

        $status = $remaining <= $threshold
            ? "⚠️ Low balance — contact admin to top up."
            : "✅ Balance looks good.";

        $message = implode("\n", [
            '💳 *Your Credits*',
            '',
            "Remaining: {$remaining} credits",
            '',
            $status,
        ]);

        $this->telegram->sendMessage($chatId, $message, ['parse_mode' => 'Markdown']);
        return response()->json(['ok' => true]);
    }

    private function handleJobs(string $chatId): JsonResponse
    {
        $user = User::where('telegram_chat_id', $chatId)->whereNotNull('activated_at')->first();

        if (! $user || $user->role !== 'vendor') {
            $this->telegram->sendMessage($chatId, 'This command is only available for vendor accounts.');
            return response()->json(['ok' => true]);
        }

        $activeJobs = Order::where('claimed_by', $user->id)
            ->whereIn('status', ['claimed', 'processing'])
            ->with('client')
            ->latest()
            ->get();

        if ($activeJobs->isEmpty()) {
            $this->telegram->sendMessage($chatId, "🔧 No active jobs right now.\n\nVisit the portal to claim from the available queue.");
            return response()->json(['ok' => true]);
        }

        $lines = ["🔧 *Your Active Jobs*", ''];
        foreach ($activeJobs as $job) {
            $statusLabel = $job->status->value === 'processing' ? '⚙️ In Progress' : '🔒 Reserved';
            $claimedAgo  = $job->claimed_at ? $job->claimed_at->diffForHumans() : '';
            $lines[]     = "#{$job->token_view} — {$statusLabel}";
            if ($claimedAgo) {
                $lines[] = "  Claimed {$claimedAgo}";
            }
        }
        $lines[] = '';
        $remaining = 5 - $activeJobs->count();
        $lines[]   = "Capacity: {$activeJobs->count()}/5 active · {$remaining} slot(s) free";

        $this->telegram->sendMessage($chatId, implode("\n", $lines), ['parse_mode' => 'Markdown']);
        return response()->json(['ok' => true]);
    }

    private function handleEarnings(string $chatId): JsonResponse
    {
        $user = User::where('telegram_chat_id', $chatId)->whereNotNull('activated_at')->first();

        if (! $user || $user->role !== 'vendor') {
            $this->telegram->sendMessage($chatId, 'This command is only available for vendor accounts.');
            return response()->json(['ok' => true]);
        }

        $pending  = (float) ($user->pending_earning_balance ?? 0);
        $payable  = (float) ($user->approved_payable_balance ?? 0);

        $message = implode("\n", [
            '💸 *Your Earnings*',
            '',
            "Pending approval: ₹" . number_format($pending, 0),
            "Payable (approved): ₹" . number_format($payable, 0),
            '',
            $payable > 0
                ? '📩 Visit the portal to request your payout.'
                : '✅ All earnings settled.',
        ]);

        $this->telegram->sendMessage($chatId, $message, ['parse_mode' => 'Markdown']);
        return response()->json(['ok' => true]);
    }

    private function handleStats(string $chatId): JsonResponse
    {
        $user = User::where('telegram_chat_id', $chatId)->whereNotNull('activated_at')->first();

        if (! $user || $user->role !== 'admin') {
            $this->telegram->sendMessage($chatId, 'This command is only available for admins.');
            return response()->json(['ok' => true]);
        }

        $todayDelivered = Order::where('status', 'delivered')
            ->whereDate('delivered_at', today())
            ->count();

        $pending    = Order::where('status', 'pending')->count();
        $active     = Order::whereIn('status', ['claimed', 'processing'])->count();
        $newClients = User::where('role', 'client')->whereDate('created_at', today())->count();

        $lowCreditClients = Client::where('credit_balance', '<=', 0)->where('status', 'active')->count();

        $message = implode("\n", [
            '📊 *Portal Stats — Today*',
            '',
            "✅ Processed: {$todayDelivered} orders",
            "⏳ Queue: {$pending} pending",
            "⚙️ Active: {$active} in progress",
            "🆕 New clients: {$newClients}",
            '',
            $lowCreditClients > 0
                ? "⚠️ {$lowCreditClients} client(s) out of credits."
                : "✅ All clients have credits.",
        ]);

        $this->telegram->sendMessage($chatId, $message, ['parse_mode' => 'Markdown']);
        return response()->json(['ok' => true]);
    }

    private function handlePending(string $chatId): JsonResponse
    {
        $user = User::where('telegram_chat_id', $chatId)->whereNotNull('activated_at')->first();

        if (! $user || $user->role !== 'admin') {
            $this->telegram->sendMessage($chatId, 'This command is only available for admins.');
            return response()->json(['ok' => true]);
        }

        $pendingPayments = \App\Models\ClientPayment::where('status', 'pending')->count();
        $pendingOrders   = Order::where('status', 'pending')->count();

        $lines = ['⏳ *Pending Items*', ''];

        if ($pendingPayments > 0) {
            $lines[] = "💳 {$pendingPayments} payment(s) awaiting approval";
            $lines[] = rtrim(config('app.url'), '/') . '/admin/finance/client-payments';
        }

        if ($pendingOrders > 0) {
            $lines[] = "📋 {$pendingOrders} order(s) in queue";
        }

        if ($pendingPayments === 0 && $pendingOrders === 0) {
            $lines[] = '✅ Nothing pending right now.';
        }

        $this->telegram->sendMessage($chatId, implode("\n", $lines), ['parse_mode' => 'Markdown']);
        return response()->json(['ok' => true]);
    }

    private function handleClearToday(string $chatId): JsonResponse
    {
        $user = User::where('telegram_chat_id', $chatId)->whereNotNull('activated_at')->first();

        if (! $user || $user->role !== 'admin') {
            $this->telegram->sendMessage($chatId, 'This command is only available for admins.');
            return response()->json(['ok' => true]);
        }

        \Illuminate\Support\Facades\Artisan::queue('app:delete-telegram-messages');
        $this->telegram->sendMessage($chatId, '🧹 Clearing today\'s messages now. This may take a moment.');

        return response()->json(['ok' => true]);
    }

    private function handleUnlink(string $chatId): JsonResponse
    {
        $user = User::where('telegram_chat_id', $chatId)
            ->whereNotNull('activated_at')
            ->whereNull('deleted_at')
            ->first();

        if (! $user) {
            $this->telegram->sendMessage($chatId, 'No portal account is linked to this Telegram.');
            return response()->json(['ok' => true]);
        }

        $user->forceFill([
            'telegram_chat_id'        => null,
            'telegram_connected_at'   => null,
            'telegram_link_token'     => null,
            'telegram_link_token_expires_at' => null,
        ])->save();

        $this->telegram->sendMessage($chatId, 'Your Telegram account has been unlinked from the portal. You will no longer receive notifications here.');

        Log::info('telegram.unlinked', array_merge(
            LogContext::currentRequest(),
            ['user_id' => $user->id, 'chat_id' => $chatId]
        ));

        return response()->json(['ok' => true]);
    }

    private function handleStart(string $chatId, string $text): JsonResponse
    {
        $parts = preg_split('/\s+/', $text, 2);
        $token = $parts[1] ?? '';

        if ($token === '') {
            $this->telegram->sendMessage($chatId, 'Link this Telegram by opening the Connect button in your portal profile.');
            return response()->json(['ok' => true]);
        }

        // ── invite_<token> — new account activation ──────────────────
        $inviteToken = str_starts_with($token, 'invite_') ? substr($token, 7) : null;

        if ($inviteToken) {
            return $this->handleInviteActivation($chatId, $inviteToken);
        }

        // ── link_<token> — link existing account ─────────────────────
        $linkToken = str_starts_with($token, 'link_') ? substr($token, 5) : $token;

        return $this->handleLinkToken($chatId, $linkToken);
    }

    // ──────────────────────────────────────────────────────────────
    // Start sub-handlers (invite + link)
    // ──────────────────────────────────────────────────────────────

    private function handleInviteActivation(string $chatId, string $inviteToken): JsonResponse
    {
        try {
            $inviteOutcome = DB::transaction(function () use ($inviteToken, $chatId) {
                $invite = PendingInvite::where('invite_token', $inviteToken)
                    ->where('expires_at', '>', now())
                    ->lockForUpdate()
                    ->first();

                if (! $invite) {
                    return null;
                }

                $existing = User::where('telegram_chat_id', $chatId)->first();
                if ($existing) {
                    return ['status' => 'already_linked'];
                }

                $seq = DB::table('portal_number_sequences')
                    ->where('role', $invite->role)
                    ->lockForUpdate()
                    ->first();

                if (! $seq) {
                    return ['status' => 'invalid_role'];
                }

                $portalNumber = $seq->next_number;

                DB::table('portal_number_sequences')
                    ->where('role', $invite->role)
                    ->update(['next_number' => $portalNumber + 1]);

                $userData = [
                    'name'                => $invite->name,
                    'role'                => $invite->role,
                    'slots'               => $invite->slots,
                    'payout_rate'         => $invite->payout_rate,
                    'telegram_chat_id'    => $chatId,
                    'activated_at'        => now(),
                    'status'              => 'active',
                    'email_verified_at'   => now(),
                    'email'               => null,
                    'password'            => null,
                    'portal_number'       => $portalNumber,
                ];

                if ($invite->role === 'client') {
                    $client = Client::create([
                        'name'   => $invite->name,
                        'slots'  => $invite->slots ?? 0,
                        'status' => 'active',
                    ]);
                    $userData['client_id'] = $client->id;
                }

                $user = User::create($userData);
                $invite->delete();

                return [
                    'status'        => 'activated',
                    'user_id'       => $user->id,
                    'name'          => $user->name,
                    'role'          => $user->role,
                    'portal_number' => $portalNumber,
                ];
            });
        } catch (UniqueConstraintViolationException) {
            $inviteOutcome = ['status' => 'duplicate_portal_number'];
        }

        if ($inviteOutcome === null) {
            $this->telegram->sendMessage($chatId, 'This invite link is invalid or has expired. Ask your admin for a new one.');
            Log::warning('telegram.invite_activation.failed', array_merge(LogContext::currentRequest(), ['chat_id' => $chatId, 'reason' => 'expired_or_missing']));
            return response()->json(['ok' => true]);
        }

        if (($inviteOutcome['status'] ?? null) === 'duplicate_portal_number') {
            $this->telegram->sendMessage($chatId, 'Account activation failed due to a conflict. Please try again or contact your admin.');
            Log::error('telegram.invite_activation.duplicate_portal_number', array_merge(LogContext::currentRequest(), ['chat_id' => $chatId]));
            return response()->json(['ok' => true]);
        }

        if (($inviteOutcome['status'] ?? null) === 'already_linked') {
            $this->telegram->sendMessage($chatId, 'This Telegram account is already linked to a portal account.');
            Log::warning('telegram.invite_activation.rejected', array_merge(LogContext::currentRequest(), ['chat_id' => $chatId, 'reason' => 'chat_already_linked']));
            return response()->json(['ok' => true]);
        }

        if (($inviteOutcome['status'] ?? null) === 'invalid_role') {
            $this->telegram->sendMessage($chatId, 'This invite link is invalid. Ask your admin for a new one.');
            Log::warning('telegram.invite_activation.rejected', array_merge(LogContext::currentRequest(), ['chat_id' => $chatId, 'reason' => 'invalid_role']));
            return response()->json(['ok' => true]);
        }

        Log::info('telegram.invite_activation.used', array_merge(LogContext::currentRequest(), [
            'chat_id'       => $chatId,
            'user_id'       => $inviteOutcome['user_id'],
            'role'          => $inviteOutcome['role'],
            'portal_number' => $inviteOutcome['portal_number'],
        ]));

        $this->telegram->sendMessage(
            $chatId,
            "Welcome {$inviteOutcome['name']}! Your account is activated.\n" .
            "Your Portal ID is: {$inviteOutcome['portal_number']}\n\n" .
            "Visit " . config('app.url') . "/login to access the portal.\n" .
            "Enter your Portal ID and we'll send you a login code."
        );

        return response()->json(['ok' => true]);
    }

    private function handleLinkToken(string $chatId, string $linkToken): JsonResponse
    {
        $linkOutcome = DB::transaction(function () use ($linkToken, $chatId) {
            $user = User::where('telegram_link_token', $linkToken)
                ->whereNotNull('activated_at')
                ->whereNull('deleted_at')
                ->lockForUpdate()
                ->first();

            if (! $user) {
                return null;
            }

            // Check token expiry (new column added in Phase 1 migration)
            if ($user->telegram_link_token_expires_at && $user->telegram_link_token_expires_at->isPast()) {
                // Clear the expired token
                $user->forceFill([
                    'telegram_link_token'            => null,
                    'telegram_link_token_expires_at' => null,
                ])->save();
                return ['status' => 'expired'];
            }

            $existing = User::where('telegram_chat_id', $chatId)
                ->where('id', '!=', $user->id)
                ->first();

            if ($existing) {
                return ['status' => 'already_linked'];
            }

            $user->forceFill([
                'telegram_chat_id'               => $chatId,
                'telegram_connected_at'          => now(),
                'telegram_link_token'            => null,
                'telegram_link_token_expires_at' => null,
            ])->save();

            return [
                'status'  => 'linked',
                'user_id' => $user->id,
                'name'    => $user->name,
                'role'    => $user->role,
            ];
        });

        if ($linkOutcome === null) {
            $this->telegram->sendMessage($chatId, 'This link is invalid or already used. Generate a new link from your dashboard.');
            Log::warning('telegram.link_token.failed', array_merge(LogContext::currentRequest(), ['chat_id' => $chatId, 'reason' => 'missing_or_expired']));
            return response()->json(['ok' => true]);
        }

        if (($linkOutcome['status'] ?? null) === 'expired') {
            $this->telegram->sendMessage($chatId, 'This link has expired (links are valid for 15 minutes). Generate a new one from your portal profile.');
            Log::warning('telegram.link_token.expired', array_merge(LogContext::currentRequest(), ['chat_id' => $chatId]));
            return response()->json(['ok' => true]);
        }

        if (($linkOutcome['status'] ?? null) === 'already_linked') {
            $this->telegram->sendMessage($chatId, 'This Telegram account is already linked to another portal account.');
            Log::warning('telegram.link_token.rejected', array_merge(LogContext::currentRequest(), ['chat_id' => $chatId, 'reason' => 'chat_already_linked']));
            return response()->json(['ok' => true]);
        }

        Log::info('telegram.link_token.used', array_merge(LogContext::currentRequest(), [
            'chat_id' => $chatId,
            'user_id' => $linkOutcome['user_id'],
            'role'    => $linkOutcome['role'],
        ]));

        $this->telegram->sendMessage(
            $chatId,
            "Telegram linked successfully.\nYou will now receive Portal PlagExpert notifications here.\n\nType /help to see available commands."
        );

        return response()->json(['ok' => true]);
    }

    // ──────────────────────────────────────────────────────────────
    // Audit log helper
    // ──────────────────────────────────────────────────────────────

    private function writeEventLog(
        string $telegramUserId,
        ?int $userId,
        string $eventType,
        ?TelegramActionToken $token,
        array $requestPayload,
        ?array $responsePayload,
        string $status,
    ): void {
        try {
            TelegramEventLog::create([
                'telegram_user_id' => $telegramUserId,
                'user_id'          => $userId,
                'event_type'       => $eventType,
                'subject_type'     => $token?->subject_type,
                'subject_id'       => $token?->subject_id,
                'request_payload'  => $requestPayload ?: null,
                'response_payload' => $responsePayload,
                'status'           => $status,
            ]);
        } catch (\Throwable $e) {
            Log::warning('telegram.event_log.write_failed', [
                'event_type' => $eventType,
                'message'    => $e->getMessage(),
            ]);
        }
    }
}
