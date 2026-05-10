<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Order;
use App\Models\PendingInvite;
use App\Models\TopupRequest;
use App\Models\User;
use App\Models\VendorPayout;
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
    public function webhook(Request $request, string $secret, TelegramService $telegramService): JsonResponse
    {
        $configuredSecret = (string) config('services.telegram.webhook_secret');
        if ($configuredSecret === '' || ! hash_equals($configuredSecret, $secret)) {
            abort(403);
        }

        $message = $request->input('message', []);
        $text = trim((string) data_get($message, 'text', ''));
        $chatId = (string) data_get($message, 'chat.id', '');

        if ($chatId === '' || $text === '') {
            return response()->json(['ok' => true]);
        }

        if ($text === '/login') {
            $user = User::where('telegram_chat_id', $chatId)
                ->whereNotNull('activated_at')
                ->whereNull('deleted_at')
                ->first();

            if (! $user) {
                $telegramService->sendMessage($chatId, 'No active account found for this Telegram. Contact your admin.');
                return response()->json(['ok' => true]);
            }

            if ($user->isFrozen()) {
                $telegramService->sendMessage($chatId, 'Your account is frozen. Contact your admin.');
                return response()->json(['ok' => true]);
            }

            $token = Str::random(48);
            $user->forceFill([
                'login_token' => $token,
                'login_token_expires_at' => now()->addMinutes(5),
            ])->save();

            $loginUrl = rtrim(config('app.url'), '/') . '/auth/telegram/' . $token;
            $sent = $telegramService->sendMessage($chatId, "Tap to login (expires in 5 minutes):\n{$loginUrl}");

            if (! $sent) {
                $user->forceFill([
                    'login_token' => null,
                    'login_token_expires_at' => null,
                ])->save();

                Log::warning('telegram.login_token.delivery_failed', array_merge(
                    LogContext::currentRequest(),
                    ['user_id' => $user->id, 'chat_id' => $chatId]
                ));
            } else {
                Log::info('telegram.login_token.issued', array_merge(
                    LogContext::currentRequest(),
                    LogContext::forUser($user, [
                        'chat_id' => $chatId,
                        'token_length' => strlen($token),
                    ])
                ));
            }

            return response()->json(['ok' => true]);
        }

        if ($text === '/myid') {
            $user = User::where('telegram_chat_id', $chatId)
                ->whereNotNull('portal_number')
                ->first();

            if (! $user) {
                $telegramService->sendMessage($chatId, 'No portal account is linked to this Telegram. Contact your admin.');
                return response()->json(['ok' => true]);
            }

            $telegramService->sendMessage(
                $chatId,
                "Your Portal ID is: {$user->portal_number}\n\nUse it to log in at " . rtrim(config('app.url'), '/') . '/login'
            );

            return response()->json(['ok' => true]);
        }

        if ($text === '/help') {
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
                    '/help — Show this message',
                ]);
            } elseif ($role === 'admin') {
                $helpText = implode("\n", [
                    '👋 *Admin Commands*',
                    '',
                    '/login — Get a portal login link',
                    '/myid — See your Portal ID',
                    '/stats — Live portal snapshot',
                    '/pending — Pending topup requests',
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

            $telegramService->sendMessage($chatId, $helpText, ['parse_mode' => 'Markdown']);
            return response()->json(['ok' => true]);
        }

        if ($text === '/status') {
            $user = User::where('telegram_chat_id', $chatId)->whereNotNull('activated_at')->first();

            if (! $user || $user->role !== 'client' || ! $user->client) {
                $telegramService->sendMessage($chatId, 'This command is only available for client accounts.');
                return response()->json(['ok' => true]);
            }

            $client = $user->client;
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
                $remaining = max(0, (int) $client->total_slots - (int) $client->slots_consumed);
                $lines[] = "💳 Credits remaining: {$remaining}";
                $body = implode("\n", $lines);
            }

            $telegramService->sendMessage($chatId, $body, ['parse_mode' => 'Markdown']);
            return response()->json(['ok' => true]);
        }

        if ($text === '/credits') {
            $user = User::where('telegram_chat_id', $chatId)->whereNotNull('activated_at')->first();

            if (! $user || $user->role !== 'client' || ! $user->client) {
                $telegramService->sendMessage($chatId, 'This command is only available for client accounts.');
                return response()->json(['ok' => true]);
            }

            $client = $user->client;
            $total = (int) $client->total_slots;
            $consumed = (int) $client->slots_consumed;
            $remaining = max(0, $total - $consumed);

            $status = $remaining <= 5
                ? "⚠️ Low balance — contact admin to top up."
                : "✅ Balance looks good.";

            $message = implode("\n", [
                '💳 *Your Credits*',
                '',
                "Used: {$consumed} / {$total} slots",
                "Remaining: {$remaining} slots",
                '',
                $status,
            ]);

            $telegramService->sendMessage($chatId, $message, ['parse_mode' => 'Markdown']);
            return response()->json(['ok' => true]);
        }

        if ($text === '/jobs') {
            $user = User::where('telegram_chat_id', $chatId)->whereNotNull('activated_at')->first();

            if (! $user || $user->role !== 'vendor') {
                $telegramService->sendMessage($chatId, 'This command is only available for vendor accounts.');
                return response()->json(['ok' => true]);
            }

            $activeJobs = Order::where('claimed_by', $user->id)
                ->whereIn('status', ['claimed', 'processing'])
                ->with('client')
                ->latest()
                ->get();

            if ($activeJobs->isEmpty()) {
                $telegramService->sendMessage($chatId, "🔧 No active jobs right now.\n\nVisit the portal to claim from the available queue.");
                return response()->json(['ok' => true]);
            }

            $lines = ["🔧 *Your Active Jobs*", ''];
            foreach ($activeJobs as $job) {
                $statusLabel = $job->status->value === 'processing' ? '⚙️ In Progress' : '🔒 Reserved';
                $claimedAgo = $job->claimed_at ? $job->claimed_at->diffForHumans() : '';
                $lines[] = "#{$job->token_view} — {$statusLabel}";
                if ($claimedAgo) {
                    $lines[] = "  Claimed {$claimedAgo}";
                }
            }
            $lines[] = '';
            $remaining = 5 - $activeJobs->count();
            $lines[] = "Capacity: {$activeJobs->count()}/5 active · {$remaining} slot(s) free";

            $telegramService->sendMessage($chatId, implode("\n", $lines), ['parse_mode' => 'Markdown']);
            return response()->json(['ok' => true]);
        }

        if ($text === '/earnings') {
            $user = User::where('telegram_chat_id', $chatId)->whereNotNull('activated_at')->first();

            if (! $user || $user->role !== 'vendor') {
                $telegramService->sendMessage($chatId, 'This command is only available for vendor accounts.');
                return response()->json(['ok' => true]);
            }

            $defaultRate = config('services.portal.vendor_payout_per_order');
            $rate = $user->payout_rate ?? $defaultRate;

            $todayDelivered = $user->daily_delivered_count ?? 0;
            $todayEarned = $todayDelivered * $rate;

            $totalDelivered = $user->delivered_orders_count ?? 0;
            $totalEarned = $totalDelivered * $rate;

            $totalPaid = VendorPayout::where('user_id', $user->id)->sum('amount');
            $pendingPayout = max(0, $totalEarned - $totalPaid);

            $message = implode("\n", [
                '💸 *Your Earnings*',
                '',
                "Today: {$todayDelivered} orders · ₹" . number_format($todayEarned, 0),
                "All time: {$totalDelivered} orders · ₹" . number_format($totalEarned, 0),
                "Paid out: ₹" . number_format($totalPaid, 0),
                "Pending: ₹" . number_format($pendingPayout, 0),
                '',
                $pendingPayout > 0
                    ? '📩 Visit the portal to request your payout.'
                    : '✅ All earnings settled.',
            ]);

            $telegramService->sendMessage($chatId, $message, ['parse_mode' => 'Markdown']);
            return response()->json(['ok' => true]);
        }

        if ($text === '/stats') {
            $user = User::where('telegram_chat_id', $chatId)->whereNotNull('activated_at')->first();

            if (! $user || $user->role !== 'admin') {
                $telegramService->sendMessage($chatId, 'This command is only available for admins.');
                return response()->json(['ok' => true]);
            }

            $defaultPrice = config('services.portal.default_client_price');
            $defaultRate  = config('services.portal.vendor_payout_per_order');

            $todayDelivered = Order::where('status', 'delivered')
                ->whereDate('delivered_at', today())
                ->with(['client', 'vendor'])
                ->get();

            $pending    = Order::where('status', 'pending')->count();
            $active     = Order::whereIn('status', ['claimed', 'processing'])->count();
            $vendors    = User::where('role', 'vendor')->where('daily_delivered_count', '>', 0)->count();
            $newClients = User::where('role', 'client')->whereDate('created_at', today())->count();

            $revenue = $todayDelivered->sum(fn ($o) => $o->client?->price_per_file ?? $defaultPrice);
            $payouts = $todayDelivered->sum(fn ($o) => $o->vendor?->payout_rate ?? $defaultRate);
            $profit  = $revenue - $payouts;

            $pendingTopups = TopupRequest::where('status', 'pending')->count();

            $message = implode("\n", [
                '📊 *Portal Stats — Today*',
                '',
                "✅ Processed: {$todayDelivered->count()} orders",
                "⏳ Queue: {$pending} pending",
                "⚙️ Active: {$active} in progress",
                "👷 Vendors working: {$vendors}",
                "🆕 New clients: {$newClients}",
                '',
                "💰 Revenue: ₹" . number_format($revenue, 0),
                "💸 Payouts: ₹" . number_format($payouts, 0),
                "📈 Net profit: ₹" . number_format($profit, 0),
                '',
                $pendingTopups > 0
                    ? "⚠️ {$pendingTopups} topup request(s) need approval."
                    : "✅ No pending topups.",
            ]);

            $telegramService->sendMessage($chatId, $message, ['parse_mode' => 'Markdown']);
            return response()->json(['ok' => true]);
        }

        if ($text === '/pending') {
            $user = User::where('telegram_chat_id', $chatId)->whereNotNull('activated_at')->first();

            if (! $user || $user->role !== 'admin') {
                $telegramService->sendMessage($chatId, 'This command is only available for admins.');
                return response()->json(['ok' => true]);
            }

            $topups = TopupRequest::with('client')
                ->where('status', 'pending')
                ->latest()
                ->take(5)
                ->get();

            if ($topups->isEmpty()) {
                $telegramService->sendMessage($chatId, '✅ No pending topup requests.');
                return response()->json(['ok' => true]);
            }

            $lines = ["⏳ *Pending Topups ({$topups->count()})*", ''];
            foreach ($topups as $i => $topup) {
                $lines[] = ($i + 1) . ". {$topup->client?->name} — {$topup->amount_requested} slots";
                if ($topup->amount_paid) {
                    $lines[] = "   ₹" . number_format($topup->amount_paid, 0) . " · UTR: {$topup->transaction_id}";
                }
            }
            $lines[] = '';
            $lines[] = "Review at: " . rtrim(config('app.url'), '/') . '/admin/topup';

            $telegramService->sendMessage($chatId, implode("\n", $lines), ['parse_mode' => 'Markdown']);
            return response()->json(['ok' => true]);
        }

        if ($text === '/cleartoday') {
            $user = User::where('telegram_chat_id', $chatId)->whereNotNull('activated_at')->first();

            if (! $user || $user->role !== 'admin') {
                $telegramService->sendMessage($chatId, 'This command is only available for admins.');
                return response()->json(['ok' => true]);
            }

            \Illuminate\Support\Facades\Artisan::queue('app:delete-telegram-messages');

            $telegramService->sendMessage($chatId, '🧹 Clearing today\'s messages now. This may take a moment.');
            return response()->json(['ok' => true]);
        }

        if (! str_starts_with($text, '/start')) {
            if (str_starts_with($text, '/')) {
                $linked = User::where('telegram_chat_id', $chatId)->exists();
                if ($linked) {
                    $telegramService->sendMessage($chatId, 'Unknown command. Type /help to see available commands.');
                }
            }
            return response()->json(['ok' => true]);
        }

        $parts = preg_split('/\s+/', $text, 2);
        $token = $parts[1] ?? '';

        if ($token === '') {
            $telegramService->sendMessage($chatId, 'Link this Telegram by opening the Connect button in your client dashboard.');
            return response()->json(['ok' => true]);
        }

        $inviteToken = str_starts_with($token, 'invite_') ? substr($token, 7) : null;

        if ($inviteToken) {
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
                    'name' => $invite->name,
                    'role' => $invite->role,
                    'slots' => $invite->slots,
                    'payout_rate' => $invite->payout_rate,
                    'telegram_chat_id' => $chatId,
                    'activated_at' => now(),
                    'status' => 'active',
                    'email_verified_at' => now(),
                    'email' => null,
                    'password' => null,
                    'portal_number' => $portalNumber,
                ];

                if ($invite->role === 'client') {
                    $client = Client::create([
                        'name' => $invite->name,
                        'slots' => $invite->slots ?? 0,
                        'status' => 'active',
                    ]);
                    $userData['client_id'] = $client->id;
                }

                $user = User::create($userData);
                $invite->delete();

                return [
                    'status' => 'activated',
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'role' => $user->role,
                    'portal_number' => $portalNumber,
                ];
            });
            } catch (UniqueConstraintViolationException) {
                $inviteOutcome = ['status' => 'duplicate_portal_number'];
            }

            if ($inviteOutcome === null) {
                $telegramService->sendMessage($chatId, 'This invite link is invalid or has expired. Ask your admin for a new one.');
                Log::warning('telegram.invite_activation.failed', array_merge(
                    LogContext::currentRequest(),
                    ['chat_id' => $chatId, 'reason' => 'expired_or_missing']
                ));

                return response()->json(['ok' => true]);
            }

            if (($inviteOutcome['status'] ?? null) === 'duplicate_portal_number') {
                $telegramService->sendMessage($chatId, 'Account activation failed due to a conflict. Please try again or contact your admin.');
                Log::error('telegram.invite_activation.duplicate_portal_number', array_merge(
                    LogContext::currentRequest(),
                    ['chat_id' => $chatId]
                ));

                return response()->json(['ok' => true]);
            }

            if (($inviteOutcome['status'] ?? null) === 'already_linked') {
                $telegramService->sendMessage($chatId, 'This Telegram account is already linked to a portal account.');
                Log::warning('telegram.invite_activation.rejected', array_merge(
                    LogContext::currentRequest(),
                    ['chat_id' => $chatId, 'reason' => 'chat_already_linked']
                ));

                return response()->json(['ok' => true]);
            }

            if (($inviteOutcome['status'] ?? null) === 'invalid_role') {
                $telegramService->sendMessage($chatId, 'This invite link is invalid. Ask your admin for a new one.');
                Log::warning('telegram.invite_activation.rejected', array_merge(
                    LogContext::currentRequest(),
                    ['chat_id' => $chatId, 'reason' => 'invalid_role']
                ));

                return response()->json(['ok' => true]);
            }

            Log::info('telegram.invite_activation.used', array_merge(
                LogContext::currentRequest(),
                [
                    'chat_id' => $chatId,
                    'user_id' => $inviteOutcome['user_id'],
                    'role' => $inviteOutcome['role'],
                    'portal_number' => $inviteOutcome['portal_number'],
                ]
            ));

            $telegramService->sendMessage(
                $chatId,
                "Welcome {$inviteOutcome['name']}! Your account is activated.\n" .
                "Your Portal ID is: {$inviteOutcome['portal_number']}\n\n" .
                "Visit " . config('app.url') . "/login to access the portal.\n" .
                "Enter your Portal ID and we'll send you a login code."
            );

            return response()->json(['ok' => true]);
        }

        $linkOutcome = DB::transaction(function () use ($token, $chatId) {
            $user = User::where('telegram_link_token', $token)
                ->whereNotNull('activated_at')
                ->whereNull('deleted_at')
                ->lockForUpdate()
                ->first();

            if (! $user) {
                return null;
            }

            $existing = User::where('telegram_chat_id', $chatId)
                ->where('id', '!=', $user->id)
                ->first();

            if ($existing) {
                return ['status' => 'already_linked'];
            }

            $user->forceFill([
                'telegram_chat_id' => $chatId,
                'telegram_connected_at' => now(),
                'telegram_link_token' => null,
            ])->save();

            return [
                'status' => 'linked',
                'user_id' => $user->id,
                'name' => $user->name,
                'role' => $user->role,
            ];
        });

        if ($linkOutcome === null) {
            $telegramService->sendMessage($chatId, 'This link is invalid or already used. Generate a new link from your dashboard.');
            Log::warning('telegram.link_token.failed', array_merge(
                LogContext::currentRequest(),
                ['chat_id' => $chatId, 'reason' => 'missing_or_expired']
            ));

            return response()->json(['ok' => true]);
        }

        if (($linkOutcome['status'] ?? null) === 'already_linked') {
            Log::warning('telegram.link_token.rejected', array_merge(
                LogContext::currentRequest(),
                ['chat_id' => $chatId, 'reason' => 'chat_already_linked']
            ));

            $telegramService->sendMessage($chatId, 'This Telegram account is already linked to another portal account.');
            return response()->json(['ok' => true]);
        }

        Log::info('telegram.link_token.used', array_merge(
            LogContext::currentRequest(),
            [
                'chat_id' => $chatId,
                'user_id' => $linkOutcome['user_id'],
                'role' => $linkOutcome['role'],
            ]
        ));

        $telegramService->sendMessage($chatId, 'Connected successfully. You will now receive portal alerts here.');

        return response()->json(['ok' => true]);
    }
}
