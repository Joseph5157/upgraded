<?php

use App\Console\Commands\AutoReleaseOrdersCommand;
use App\Console\Commands\CloseDayCommand;
use App\Console\Commands\PurgeOrderFilesCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Release overdue claimed orders back to the pending pool every minute
Schedule::command(AutoReleaseOrdersCommand::class)->everyMinute();

// Close-of-day ledger snapshot at 11:59 PM every night
Schedule::command(CloseDayCommand::class)->dailyAt('23:59');

// Purge all order files from disk every night at 2:00 AM
Schedule::command(PurgeOrderFilesCommand::class)->dailyAt('02:00');

// Prune expired sessions from the database nightly at 3:00 AM
Schedule::call(function () {
    $lifetime = config('session.lifetime');
    DB::table('sessions')
        ->where('last_activity', '<', now()->subMinutes($lifetime)->timestamp)
        ->delete();
})->dailyAt('03:00')->name('prune-expired-sessions');