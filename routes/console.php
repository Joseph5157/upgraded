<?php

use App\Console\Commands\AutoReleaseOrdersCommand;
use App\Console\Commands\CleanupLinkOrdersCommand;
use App\Console\Commands\CloseDayCommand;
use App\Console\Commands\PurgeOrderFilesCommand;
use App\Console\Commands\DeleteTelegramMessagesCommand;
use App\Console\Commands\SetTelegramBotCommandsCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Release orders that have exceeded their claim windows — runs every minute.
// withoutOverlapping() prevents concurrent runs on multi-worker deployments.
Schedule::command(AutoReleaseOrdersCommand::class)->everyMinute()->withoutOverlapping();

// Close-of-day ledger snapshot at 11:59 PM every night
Schedule::command(CloseDayCommand::class)->dailyAt('23:59');

// Purge all order files from disk every night at 2:00 AM
Schedule::command(PurgeOrderFilesCommand::class)->dailyAt('02:00');

// Clean up link-based orders older than 24 hours — runs every hour
Schedule::command(CleanupLinkOrdersCommand::class)->hourly();

// Keep Telegram bot command list registered — runs once daily at 06:00
Schedule::command(SetTelegramBotCommandsCommand::class)->dailyAt('06:00');

// Delete all bot messages sent today at 23:55 before the midnight session expiry
Schedule::command(DeleteTelegramMessagesCommand::class)
    ->dailyAt('23:55')
    ->withoutOverlapping()
    ->runInBackground();

// Prune expired sessions from the database nightly when the database session driver is enabled
Schedule::call(function () {
    if (config('session.driver') !== 'database') {
        return;
    }

    $lifetime = config('session.lifetime');
    DB::table('sessions')
        ->where('last_activity', '<', now()->subMinutes($lifetime)->timestamp)
        ->delete();
})->dailyAt('03:00')->name('prune-expired-sessions');
