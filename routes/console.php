<?php

use App\Console\Commands\AutoReleaseOrdersCommand;
use App\Console\Commands\CloseDayCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Release overdue claimed orders back to the pending pool every minute
Schedule::command(AutoReleaseOrdersCommand::class)->everyMinute();

// Close-of-day ledger snapshot at 11:59 PM every night
Schedule::command(CloseDayCommand::class)->dailyAt('23:59');
