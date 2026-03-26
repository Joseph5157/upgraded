<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    public function sendMessage(string $chatId, string $text): bool
    {
        $botToken = config('services.telegram.bot_token');
        if (! $botToken || ! $chatId) {
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
                return true;
            }

            Log::warning('Telegram send failed: ' . $response->body());
            return false;
        } catch (\Throwable $e) {
            Log::error('Telegram send exception: ' . $e->getMessage());
            return false;
        }
    }
}
