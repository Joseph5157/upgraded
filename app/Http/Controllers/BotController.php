<?php

namespace App\Http\Controllers;

use App\Models\PendingInvite;
use App\Models\User;
use App\Services\TelegramService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
        $text    = trim((string) data_get($message, 'text', ''));
        $chatId  = (string) data_get($message, 'chat.id', '');

        if ($chatId === '' || $text === '') {
            return response()->json(['ok' => true]);
        }

        // ── /login ────────────────────────────────────────────────────────────
        if ($text === '/login') {
            $user = User::where('telegram_chat_id', $chatId)
                ->whereNotNull('activated_at')
                ->first();

            if (! $user) {
                $telegramService->sendMessage($chatId,
                    'No active account found for this Telegram. Contact your admin.');
                return response()->json(['ok' => true]);
            }

            if ($user->isFrozen()) {
                $telegramService->sendMessage($chatId,
                    'Your account is frozen. Contact your admin.');
                return response()->json(['ok' => true]);
            }

            $token = Str::random(48);
            $user->update([
                'login_token'            => $token,
                'login_token_expires_at' => now()->addMinutes(5),
            ]);

            $loginUrl = rtrim(config('app.url'), '/') . '/auth/telegram/' . $token;
            $telegramService->sendMessage($chatId,
                "Tap to login (expires in 5 minutes):\n{$loginUrl}");

            return response()->json(['ok' => true]);
        }

        // ── /start ────────────────────────────────────────────────────────────
        if (! str_starts_with($text, '/start')) {
            return response()->json(['ok' => true]);
        }

        $parts   = preg_split('/\s+/', $text, 2);
        $token   = $parts[1] ?? '';

        if ($token === '') {
            $telegramService->sendMessage($chatId,
                'Link this Telegram by opening the Connect button in your client dashboard.');
            return response()->json(['ok' => true]);
        }

        // ── invite_ token (checked before telegram_link_token so the prefix
        //    doesn't accidentally match the link-token lookup below) ───────────
        $inviteToken = str_starts_with($token, 'invite_') ? substr($token, 7) : null;

        if ($inviteToken) {
            $invite = PendingInvite::where('invite_token', $inviteToken)
                ->where('expires_at', '>', now())
                ->first();

            if (! $invite) {
                $telegramService->sendMessage($chatId,
                    'This invite link is invalid or has expired. Ask your admin for a new one.');
                return response()->json(['ok' => true]);
            }

            $existing = User::where('telegram_chat_id', $chatId)->first();
            if ($existing) {
                $telegramService->sendMessage($chatId,
                    'This Telegram account is already linked to a portal account.');
                return response()->json(['ok' => true]);
            }

            $user = User::create([
                'name'              => $invite->name,
                'role'              => $invite->role,
                'slots'             => $invite->slots,
                'payout_rate'       => $invite->payout_rate,
                'telegram_chat_id'  => $chatId,
                'activated_at'      => now(),
                'status'            => 'active',
                'email_verified_at' => now(),
                'email'             => null,
                'password'          => null,
            ]);

            $invite->delete();

            $telegramService->sendMessage($chatId,
                "Welcome {$user->name}! Your account is activated.\n\nSend /login anytime to access the portal.");

            return response()->json(['ok' => true]);
        }

        // ── telegram_link_token (legacy Telegram connect from dashboard) ──────
        $user = User::where('telegram_link_token', $token)->first();

        if (! $user) {
            $telegramService->sendMessage($chatId,
                'This link is invalid or already used. Generate a new link from your dashboard.');
            return response()->json(['ok' => true]);
        }

        $existing = User::where('telegram_chat_id', $chatId)->where('id', '!=', $user->id)->first();
        if ($existing) {
            Log::warning("Telegram chat {$chatId} already linked to user #{$existing->id}; rejecting link for user #{$user->id}");
            $telegramService->sendMessage($chatId,
                'This Telegram account is already linked to another portal account.');
            return response()->json(['ok' => true]);
        }

        $user->update([
            'telegram_chat_id'      => $chatId,
            'telegram_connected_at' => now(),
            'telegram_link_token'   => null,
        ]);

        $telegramService->sendMessage($chatId,
            "Connected successfully.\nYou will now receive portal alerts here.");

        return response()->json(['ok' => true]);
    }
}
