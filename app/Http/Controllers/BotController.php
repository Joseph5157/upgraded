<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\PendingInvite;
use App\Models\User;
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

        if (! str_starts_with($text, '/start')) {
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
