<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    public function sendMessage(string $chatId, string $text): bool
    {
        $botToken = config('services.telegram.bot_token');
        $chatId = trim($chatId);

        if (! $botToken || $chatId === '') {
            Log::warning('Telegram send skipped: missing bot token or chat id.');
            return false;
        }

        try {
            $response = Http::timeout(12)->post(
                "https://api.telegram.org/bot{$botToken}/sendMessage",
                [
                    'chat_id' => $chatId,
                    'text' => $text,
                    'disable_web_page_preview' => true,
                ]
            );

            if ($response->successful()) {
                Log::info('Telegram send succeeded.', ['chat_id' => $chatId]);
                return true;
            }

            Log::warning('Telegram send failed.', [
                'chat_id' => $chatId,
                'body' => $response->body(),
            ]);
            return false;
        } catch (\Throwable $e) {
            Log::error('Telegram send exception.', [
                'chat_id' => $chatId,
                'message' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
