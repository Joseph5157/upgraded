<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    public function sendMessage(string $chatId, string $text, ?array $replyMarkup = null, array $options = []): bool
    {
        $botToken = config('services.telegram.bot_token');
        $chatId = trim($chatId);

        if (! $botToken || $chatId === '') {
            Log::warning('Telegram send skipped: missing bot token or chat id.');
            return false;
        }

        if (app()->environment('testing') && config('services.telegram.testing_fake', true)) {
            Log::info('Telegram send faked in testing.', [
                'chat_id' => $chatId,
                'message_length' => mb_strlen($text),
                'has_reply_markup' => $replyMarkup !== null,
            ]);

            return true;
        }

        try {
            $payload = [
                'chat_id' => $chatId,
                'text' => $text,
                'disable_web_page_preview' => true,
            ];

            if ($replyMarkup !== null) {
                $payload['reply_markup'] = $replyMarkup;
            }

            if ($options !== []) {
                $payload = array_merge($payload, $options);
            }

            $response = Http::timeout(12)->post(
                "https://api.telegram.org/bot{$botToken}/sendMessage",
                $payload
            );

            if ($response->successful()) {
                Log::info('Telegram send succeeded.', ['chat_id' => $chatId]);
                return true;
            }

            Log::warning('Telegram send failed.', [
                'chat_id' => $chatId,
                'status' => $response->status(),
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
