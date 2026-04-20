<?php

namespace App\Jobs;

use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendTelegramNotification implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public bool $deleteWhenMissingModels = true;
    public int $tries = 3;
    public int $timeout = 30;
    public array $backoff = [10, 30, 60];

    public function __construct(public readonly Order $order) {}

    public function handle(): void
    {
        $botToken = config('services.telegram.bot_token');
        $chatId   = config('services.telegram.vendor_chat_id');

        if (! $botToken || ! $chatId) {
            Log::warning('Telegram credentials not configured. Skipping vendor notification.');
            return;
        }

        $this->order->loadMissing(['client', 'files']);

        $message = $this->buildMessage();

        $response = Http::timeout(20)->post(
            "https://api.telegram.org/bot{$botToken}/sendMessage",
            [
                'chat_id'                  => $chatId,
                'text'                     => $message,
                'parse_mode'               => 'Markdown',
                'disable_web_page_preview' => true,
            ]
        );

        if ($response->successful()) {
            Log::info("Telegram notification sent for order #{$this->order->id}");
        } else {
            Log::error("Telegram API error for order #{$this->order->id}: " . $response->body());
            // Throw so the job retries on non-2xx responses
            throw new \RuntimeException('Telegram API returned non-success: ' . $response->status());
        }
    }

    private function buildMessage(): string
    {
        $order      = $this->order;
        $clientName = $this->escapeMarkdown($order->client->name ?? 'Unknown Client');

        $message  = " *New Order Received!*\n\n";
        $message .= " *Order ID:* #{$order->id}\n";
        $message .= " *Tracking:* `{$order->token_view}`\n";
        $message .= " *Client:* {$clientName}\n";
        $message .= " *Files:* {$order->files_count}\n";
        $message .= " *Source:* " . ucfirst($order->source) . "\n";

        if ($order->notes) {
            $message .= " *Notes:* " . $this->escapeMarkdown(mb_substr($order->notes, 0, 100)) . "\n";
        }

        if ($order->files_count >= 10) {
            $message .= "\n *LARGE ORDER - High Priority!*\n";
        }

        $message .= "\n [Open Dashboard](" . route('dashboard') . ")";

        return $message;
    }

    private function escapeMarkdown(string $text): string
    {
        return str_replace(['_', '*', '[', '`'], ['\_', '\*', '\[', '\`'], $text);
    }
}
