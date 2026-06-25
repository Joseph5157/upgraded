<?php

namespace App\Console\Commands;

use App\Services\TelegramService;
use Illuminate\Console\Command;

class TelegramDeleteWebhookCommand extends Command
{
    protected $signature   = 'telegram:delete-webhook';
    protected $description = 'Remove the registered Telegram bot webhook.';

    public function handle(TelegramService $telegram): int
    {
        $this->info('Removing webhook...');

        $result = $telegram->deleteWebhook();

        if (! $result) {
            $this->error('Webhook removal failed. Check logs for details.');
            return Command::FAILURE;
        }

        if (isset($result['ok']) && $result['ok'] === true) {
            $this->info('Webhook removed successfully. The bot will now use long polling.');
            return Command::SUCCESS;
        }

        $this->error('Telegram returned an error: ' . json_encode($result));
        return Command::FAILURE;
    }
}
