<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BotController extends Controller
{
    public function webhook(Request $request, string $secret, TelegramService $telegramService)
    {
        $configuredSecret = (string) config('services.telegram.webhook_secret');
        if ($configuredSecret === '' || ! hash_equals($configuredSecret, $secret)) {
            abort(403);
        }

        $message = $request->input('message', []);
        $text = trim((string) data_get($message, 'text', ''));
        $chatId = (string) data_get($message, 'chat.id', '');

        if ($chatId === '') {
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

        $user = User::where('telegram_link_token', $token)->first();
        if (! $user) {
            $telegramService->sendMessage($chatId, 'This link is invalid or already used. Generate a new link from your dashboard.');
            return response()->json(['ok' => true]);
        }

        $existing = User::where('telegram_chat_id', $chatId)->where('id', '!=', $user->id)->first();
        if ($existing) {
            Log::warning("Telegram chat {$chatId} already linked to user #{$existing->id}; rejecting link for user #{$user->id}");
            $telegramService->sendMessage($chatId, 'This Telegram account is already linked to another portal account.');
            return response()->json(['ok' => true]);
        }

        $user->update([
            'telegram_chat_id' => $chatId,
            'telegram_connected_at' => now(),
            'telegram_link_token' => null,
        ]);

        $telegramService->sendMessage($chatId, "Connected successfully.\nYou will now receive portal alerts here.");

        return response()->json(['ok' => true]);
    }
}
