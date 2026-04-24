<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\User;
use App\Services\TelegramService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendOrderCompletedTelegramNotification implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, Queueable, InteractsWithQueue, SerializesModels;

    public bool $deleteWhenMissingModels = true;
    public int $tries = 3;
    public int $timeout = 30;
    public array $backoff = [10, 30, 60];
    public int $uniqueFor = 86400;

    public function __construct(public readonly Order $order) {}

    public function uniqueId(): string
    {
        return 'order-completed-telegram:' . $this->order->getKey();
    }

    public function handle(TelegramService $telegramService): void
    {
        $order = $this->order->fresh(['client', 'creator', 'client.user']);

        if (! $order) {
            Log::warning('Telegram completion notification skipped because the order no longer exists.', [
                'order_id' => $this->order->getKey(),
            ]);

            return;
        }

        $recipient = $this->resolveClientRecipient($order);

        if (! $recipient?->telegram_chat_id) {
            Log::info("Skipping client Telegram completion alert for order #{$order->id}: client not connected.");
            return;
        }

        $botToken = config('services.telegram.bot_token');
        $chatId = trim((string) $recipient->telegram_chat_id);

        if (! $botToken || $chatId === '') {
            Log::warning('Telegram send skipped: missing bot token or chat id.', [
                'order_id' => $order->id,
            ]);
            return;
        }

        $sent = $telegramService->sendMessage(
            $chatId,
            $this->buildMessage($order),
            null,
            ['parse_mode' => 'Markdown']
        );

        if (! $sent) {
            throw new \RuntimeException('Failed to send the order-completed Telegram notification.');
        }
    }

    protected function resolveClientRecipient(Order $order): ?User
    {
        if ($order->creator instanceof User) {
            return $order->creator;
        }

        return $order->client?->user;
    }

    protected function buildMessage(Order $order): string
    {
        return implode("\n", [
            'âœ… *Your report is ready to download*',
            '',
            '*Order ID:* `#' . $order->id . '`',
            '*Tracking ID:* `' . $this->escapeMarkdown($order->token_view) . '`',
            '',
            '_Open your portal dashboard to download the files._',
        ]);
    }

    protected function escapeMarkdown(string $text): string
    {
        return str_replace(['_', '*', '[', '`'], ['\_', '\*', '\[', '\`'], $text);
    }
}
