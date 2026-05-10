<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SetTelegramBotCommandsCommand extends Command
{
    protected $signature = 'app:set-telegram-bot-commands';
    protected $description = 'Register the bot command list with Telegram via setMyCommands';

    public function handle(): int
    {
        $token = config('services.telegram.bot_token');

        if (! $token) {
            $this->error('TELEGRAM_BOT_TOKEN is not configured.');
            return Command::FAILURE;
        }

        $commands = [
            ['command' => 'login', 'description' => 'Get a login link for the portal'],
            ['command' => 'myid',  'description' => 'See your Portal ID'],
            ['command' => 'help',  'description' => 'How to use this bot'],
        ];

        $url = "https://api.telegram.org/bot{$token}/setMyCommands";

        try {
            $response = Http::timeout(15)->post($url, [
                'commands' => $commands,
                'scope'    => ['type' => 'default'],
            ]);
        } catch (\Throwable $e) {
            Log::error('telegram.bot_commands.exception', ['message' => $e->getMessage()]);
            $this->error('Request failed: ' . $e->getMessage());
            return Command::FAILURE;
        }

        if ($response->successful() && $response->json('ok')) {
            Log::info('telegram.bot_commands.registered', [
                'commands' => array_column($commands, 'command'),
            ]);
            $this->info('Bot commands registered successfully.');
            return Command::SUCCESS;
        }

        Log::warning('telegram.bot_commands.failed', [
            'status' => $response->status(),
            'body'   => $response->body(),
        ]);
        $this->error('Failed to register bot commands: ' . $response->body());
        return Command::FAILURE;
    }
}
