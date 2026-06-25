<?php

namespace App\Console\Commands;

use App\Services\TelegramService;
use Illuminate\Console\Command;

class TelegramSetWebhookCommand extends Command
{
    protected $signature   = 'telegram:set-webhook';
    protected $description = 'Register the Telegram bot webhook with the Telegram API.';

    public function handle(TelegramService $telegram): int
    {
        $token  = config('telegram.bot_token') ?? config('services.telegram.bot_token');
        $secret = config('telegram.webhook_secret') ?? config('services.telegram.webhook_secret');

        if (empty($token)) {
            $this->error('TELEGRAM_BOT_TOKEN is not set.');
            return Command::FAILURE;
        }

        if (empty($secret)) {
            $this->warn('TELEGRAM_WEBHOOK_SECRET is not set — the webhook will not be secured with a secret token.');
        }

        $this->info('Registering webhook...');

        $result = $telegram->setWebhook();

        if (! $result) {
            $this->error('Webhook registration failed. Check logs for details.');
            return Command::FAILURE;
        }

        if (isset($result['ok']) && $result['ok'] === true) {
            $this->info('Webhook registered successfully.');

            // Show current webhook info
            $info = $telegram->getWebhookInfo();
            if ($info && isset($info['result'])) {
                $this->line('');
                $this->line('Current webhook info:');
                $this->line('  URL:             ' . ($info['result']['url'] ?? '(none)'));
                $this->line('  Pending updates: ' . ($info['result']['pending_update_count'] ?? 0));
                $has_secret = ! empty($info['result']['secret_token'] ?? '');
                $this->line('  Secret token:    ' . ($has_secret ? 'set' : 'not set'));
            }

            return Command::SUCCESS;
        }

        $this->error('Telegram returned an error: ' . json_encode($result));
        return Command::FAILURE;
    }
}
