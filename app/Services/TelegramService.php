<?php

namespace App\Services;

use App\Models\TelegramSentMessage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    public function sendMessage(string $chatId, string $text, array $options = []): int|false
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
                'has_options' => $options !== [],
            ]);

            return 1;
        }

        try {
            $payload = array_merge([
                'chat_id' => $chatId,
                'text' => $text,
                'disable_web_page_preview' => true,
            ], $options);

            $response = Http::timeout(12)->post(
                "https://api.telegram.org/bot{$botToken}/sendMessage",
                $payload
            );

            if ($response->successful()) {
                $messageId = (int) $response->json('result.message_id');
                Log::info('Telegram send succeeded.', ['chat_id' => $chatId, 'message_id' => $messageId]);

                try {
                    TelegramSentMessage::create([
                        'chat_id'    => $chatId,
                        'message_id' => $messageId,
                        'sent_at'    => now(),
                    ]);
                } catch (\Throwable $e) {
                    Log::warning('telegram.sent_message.store_failed', [
                        'chat_id' => $chatId,
                        'message' => $e->getMessage(),
                    ]);
                }

                return $messageId;
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

    public function sendWithKeyboard(string $chatId, string $text, array $keyboard): bool
    {
        return $this->sendMessage($chatId, $text, [
            'reply_markup' => [
                'keyboard' => $keyboard,
                'resize_keyboard' => true,
                'persistent' => true,
            ],
        ]) !== false;
    }
}
