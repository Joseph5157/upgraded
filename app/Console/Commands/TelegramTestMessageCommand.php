<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\TelegramService;
use Illuminate\Console\Command;

class TelegramTestMessageCommand extends Command
{
    protected $signature = 'telegram:test-message
                            {userId : Portal user ID or Telegram chat ID}
                            {--chat : Treat the argument as a raw Telegram chat ID instead of a portal user ID}';

    protected $description = 'Send a test Telegram message to a portal user or a raw chat ID.';

    public function handle(TelegramService $telegram): int
    {
        $id = $this->argument('userId');

        if ($this->option('chat')) {
            $chatId = (string) $id;
            $name   = "chat {$chatId}";
        } else {
            $user = User::find((int) $id);

            if (! $user) {
                $this->error("User #{$id} not found.");
                return Command::FAILURE;
            }

            if (! $user->telegram_chat_id) {
                $this->error("User #{$id} ({$user->name}) has no linked Telegram chat ID.");
                return Command::FAILURE;
            }

            $chatId = $user->telegram_chat_id;
            $name   = "{$user->name} (chat {$chatId})";
        }

        $this->info("Sending test message to {$name}...");

        $messageId = $telegram->sendMessage(
            $chatId,
            "✅ Test message from Portal PlagExpert\n\nIf you received this, Telegram notifications are working correctly.",
        );

        if (! $messageId) {
            $this->error('Failed to send test message. Check logs for details.');
            return Command::FAILURE;
        }

        $this->info("Test message sent (message_id: {$messageId}).");
        return Command::SUCCESS;
    }
}
