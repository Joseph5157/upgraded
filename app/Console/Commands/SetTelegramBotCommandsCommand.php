<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SetTelegramBotCommandsCommand extends Command
{
    protected $signature   = 'app:set-telegram-bot-commands';
    protected $description = 'Registers scoped bot commands with Telegram.';

    public function handle(): int
    {
        $token = config('services.telegram.bot_token');

        if (empty($token)) {
            $this->error('TELEGRAM_BOT_TOKEN not set.');
            return Command::FAILURE;
        }

        $set = function (array $commands, array $scope) use ($token): bool {
            $response = Http::post("https://api.telegram.org/bot{$token}/setMyCommands", [
                'commands' => $commands,
                'scope'    => $scope,
            ]);
            return $response->json('ok') === true;
        };

        $privateCommands = [
            ['command' => 'login', 'description' => 'Get a login link for the portal'],
            ['command' => 'myid',  'description' => 'See your Portal ID'],
            ['command' => 'help',  'description' => 'See available commands'],
        ];

        $groupCommands = [
            ['command' => 'jobs',       'description' => 'View your active jobs'],
            ['command' => 'earnings',   'description' => 'See your earnings summary'],
            ['command' => 'stats',      'description' => 'Live portal snapshot'],
            ['command' => 'pending',    'description' => 'Pending topup requests'],
            ['command' => 'cleartoday', 'description' => "Delete todays bot messages"],
            ['command' => 'help',       'description' => 'See available commands'],
        ];

        $adminCommands = [
            ['command' => 'stats',      'description' => 'Live portal snapshot'],
            ['command' => 'pending',    'description' => 'Pending topup requests'],
            ['command' => 'cleartoday', 'description' => "Delete todays bot messages"],
            ['command' => 'help',       'description' => 'See available commands'],
        ];

        $results = [
            $set($privateCommands, ['type' => 'default']),
            $set($privateCommands, ['type' => 'all_private_chats']),
            $set($groupCommands,   ['type' => 'all_group_chats']),
            $set($adminCommands,   ['type' => 'all_chat_administrators']),
        ];

        if (! in_array(false, $results, true)) {
            $this->info('All scoped commands registered successfully.');
            Log::info('Telegram bot commands registered.');
            return Command::SUCCESS;
        }

        $this->warn('Some scopes failed — check logs.');
        return Command::FAILURE;
    }
}
