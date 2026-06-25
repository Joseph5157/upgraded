<?php

namespace App\Services;

use App\Models\TelegramSentMessage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    // ──────────────────────────────────────────────────────────────
    // Internal helpers
    // ──────────────────────────────────────────────────────────────

    protected function botToken(): ?string
    {
        return config('telegram.bot_token') ?? config('services.telegram.bot_token');
    }

    protected function isFaked(): bool
    {
        return app()->environment('testing')
            && (bool) (config('telegram.testing_fake') ?? config('services.telegram.testing_fake', true));
    }

    /**
     * Call a Telegram Bot API method.
     *
     * Returns the decoded JSON response body on success, null on failure.
     */
    protected function call(string $method, array $payload = []): ?array
    {
        $botToken = $this->botToken();

        if (! $botToken) {
            Log::warning("Telegram call skipped: missing bot token.", ['method' => $method]);
            return null;
        }

        if ($this->isFaked()) {
            Log::info("Telegram API call faked in testing.", [
                'method'  => $method,
                'payload' => array_keys($payload),
            ]);
            return ['ok' => true, 'result' => true];
        }

        $baseUrl = config('telegram.api_base_url', 'https://api.telegram.org/bot');

        try {
            $response = Http::timeout(12)->post("{$baseUrl}{$botToken}/{$method}", $payload);

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning("Telegram API call failed.", [
                'method' => $method,
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            return null;
        } catch (\Throwable $e) {
            Log::error("Telegram API exception.", [
                'method'  => $method,
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }

    // ──────────────────────────────────────────────────────────────
    // Messaging
    // ──────────────────────────────────────────────────────────────

    /**
     * Send a text message to a chat.
     *
     * Returns the Telegram message_id on success, false on failure.
     */
    public function sendMessage(string $chatId, string $text, array $options = []): int|false
    {
        $chatId = trim($chatId);

        if ($chatId === '') {
            Log::warning('Telegram sendMessage skipped: empty chat_id.');
            return false;
        }

        if ($this->isFaked()) {
            Log::info('Telegram send faked in testing.', [
                'chat_id'        => $chatId,
                'message_length' => mb_strlen($text),
                'has_options'    => $options !== [],
            ]);
            return 1;
        }

        $payload = array_merge([
            'chat_id'                  => $chatId,
            'text'                     => $text,
            'disable_web_page_preview' => true,
        ], $options);

        $result = $this->call('sendMessage', $payload);

        if (! $result) {
            return false;
        }

        $messageId = (int) ($result['result']['message_id'] ?? 0);

        if ($messageId) {
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
        }

        return $messageId ?: false;
    }

    /**
     * Send a message with an inline keyboard (for buttons).
     *
     * $replyMarkup must be the full reply_markup array, e.g.:
     *   ['inline_keyboard' => [[['text' => 'Click', 'callback_data' => 'a:uuid']]]]
     *
     * Returns the Telegram message_id on success, false on failure.
     */
    public function sendMessageWithInlineKeyboard(
        string $chatId,
        string $text,
        array $replyMarkup,
        array $extraOptions = [],
    ): int|false {
        return $this->sendMessage($chatId, $text, array_merge($extraOptions, [
            'parse_mode'   => 'HTML',
            'reply_markup' => $replyMarkup,
        ]));
    }

    /**
     * Legacy helper — sends a reply keyboard (persistent menu).
     *
     * Prefer sendMessageWithInlineKeyboard for action buttons.
     */
    public function sendWithKeyboard(string $chatId, string $text, array $keyboard): bool
    {
        return $this->sendMessage($chatId, $text, [
            'reply_markup' => [
                'keyboard'        => $keyboard,
                'resize_keyboard' => true,
                'persistent'      => true,
            ],
        ]) !== false;
    }

    /**
     * Edit the text (and optionally the inline keyboard) of a previously sent message.
     *
     * Returns the updated message data array on success, null on failure.
     */
    public function editMessageText(
        string $chatId,
        string|int $messageId,
        string $text,
        ?array $replyMarkup = null,
        array $extraOptions = [],
    ): ?array {
        $payload = array_merge([
            'chat_id'                  => $chatId,
            'message_id'               => (int) $messageId,
            'text'                     => $text,
            'parse_mode'               => 'HTML',
            'disable_web_page_preview' => true,
        ], $extraOptions);

        if ($replyMarkup !== null) {
            $payload['reply_markup'] = $replyMarkup;
        }

        return $this->call('editMessageText', $payload);
    }

    // ──────────────────────────────────────────────────────────────
    // Callback query
    // ──────────────────────────────────────────────────────────────

    /**
     * Answer a callback_query (the tap on an inline keyboard button).
     *
     * Must always be called within 10 seconds of receiving the callback
     * to dismiss the loading indicator in Telegram.
     *
     * @param  string  $callbackQueryId  From the incoming callback_query.id
     * @param  string  $text             Short toast shown to the user (max 200 chars)
     * @param  bool    $showAlert        Show as full-screen alert instead of toast
     */
    public function answerCallbackQuery(
        string $callbackQueryId,
        string $text = '',
        bool $showAlert = false,
    ): bool {
        $payload = ['callback_query_id' => $callbackQueryId];

        if ($text !== '') {
            $payload['text']       = mb_substr($text, 0, 200);
            $payload['show_alert'] = $showAlert;
        }

        return (bool) $this->call('answerCallbackQuery', $payload);
    }

    // ──────────────────────────────────────────────────────────────
    // Inline query
    // ──────────────────────────────────────────────────────────────

    /**
     * Answer an inline_query with a list of results.
     *
     * @param  string  $inlineQueryId  From the incoming inline_query.id
     * @param  array   $results        Array of InlineQueryResult objects
     * @param  bool    $isPersonal     Cache results per-user (recommended for sensitive data)
     * @param  int     $cacheTime      How long Telegram caches the result in seconds
     */
    public function answerInlineQuery(
        string $inlineQueryId,
        array $results,
        bool $isPersonal = true,
        int $cacheTime = 30,
    ): bool {
        return (bool) $this->call('answerInlineQuery', [
            'inline_query_id' => $inlineQueryId,
            'results'         => $results,
            'is_personal'     => $isPersonal,
            'cache_time'      => $cacheTime,
        ]);
    }

    // ──────────────────────────────────────────────────────────────
    // Webhook management
    // ──────────────────────────────────────────────────────────────

    /**
     * Register the webhook URL with Telegram.
     *
     * The URL is derived from APP_PUBLIC_URL + the portal webhook route.
     * Requires config('telegram.webhook_secret') to be set.
     */
    public function setWebhook(): ?array
    {
        $url    = rtrim(config('app.url'), '/') . route('telegram.webhook', [], false);
        $secret = config('telegram.webhook_secret') ?? config('services.telegram.webhook_secret');

        $payload = [
            'url'             => $url,
            'allowed_updates' => ['message', 'callback_query', 'inline_query'],
        ];

        if ($secret) {
            $payload['secret_token'] = $secret;
        }

        Log::info('Telegram setWebhook called.', ['url' => $url]);

        return $this->call('setWebhook', $payload);
    }

    /**
     * Remove the registered webhook from Telegram.
     */
    public function deleteWebhook(): ?array
    {
        Log::info('Telegram deleteWebhook called.');

        return $this->call('deleteWebhook', ['drop_pending_updates' => false]);
    }

    /**
     * Get current webhook info from Telegram.
     */
    public function getWebhookInfo(): ?array
    {
        return $this->call('getWebhookInfo');
    }
}
