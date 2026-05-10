<?php

namespace App\Console\Commands;

use App\Models\TelegramSentMessage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DeleteTelegramMessagesCommand extends Command
{
    protected $signature = 'app:delete-telegram-messages';
    protected $description = 'Deletes all Telegram messages sent today via the bot.';

    public function handle(): int
    {
        $messages = TelegramSentMessage::whereDate('sent_at', today())->get();

        if ($messages->isEmpty()) {
            $this->info('No messages to delete.');
            return Command::SUCCESS;
        }

        $token = config('services.telegram.bot_token');

        if (! $token) {
            $this->error('TELEGRAM_BOT_TOKEN not configured.');
            return Command::FAILURE;
        }

        $deleted = 0;
        $failed  = 0;
        $url     = "https://api.telegram.org/bot{$token}/deleteMessage";

        foreach ($messages as $message) {
            try {
                $response = Http::timeout(10)->post($url, [
                    'chat_id'    => $message->chat_id,
                    'message_id' => $message->message_id,
                ]);

                if ($response->successful() && $response->json('result') === true) {
                    $deleted++;
                } else {
                    $failed++;
                    Log::warning('telegram.delete_message.failed', [
                        'chat_id'    => $message->chat_id,
                        'message_id' => $message->message_id,
                        'status'     => $response->status(),
                        'body'       => $response->body(),
                    ]);
                }
            } catch (\Throwable $e) {
                $failed++;
                Log::warning('telegram.delete_message.exception', [
                    'chat_id'    => $message->chat_id,
                    'message_id' => $message->message_id,
                    'message'    => $e->getMessage(),
                ]);
            }

            usleep(50000);
        }

        TelegramSentMessage::whereDate('sent_at', today())->delete();

        $this->info("Done. Deleted: {$deleted}, Failed: {$failed}");
        return Command::SUCCESS;
    }
}
