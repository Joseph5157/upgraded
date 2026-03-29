<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Order;
use App\Models\OrderFile;
use App\Models\OrderReport;
use App\Models\Client;

class HealthCheckCommand extends Command
{
    protected $signature = 'app:health-check';
    protected $description = 'Check all system health indicators';

    public function handle(): int
    {
        $this->info('========================================');
        $this->info('       PLAGEXPERT HEALTH CHECK          ');
        $this->info('========================================');
        $this->newLine();

        // CHECK 1 - R2 Storage
        $this->info('CHECK 1: R2 / Storage Files');
        $this->line('  FILESYSTEM_DISK    : ' . config('filesystems.default'));
        $this->line('  R2_BUCKET          : ' . env('R2_BUCKET'));
        $this->line('  R2_ENDPOINT        : ' . env('R2_ENDPOINT'));
        $localFiles   = OrderFile::where('disk', 'local')->orWhereNull('disk')->count();
        $r2Files      = OrderFile::where('disk', 'r2')->count();
        $localReports = OrderReport::where('ai_report_disk', 'local')
                            ->orWhere('plag_report_disk', 'local')->count();
        $r2Reports    = OrderReport::where('ai_report_disk', 'r2')->count();
        $localFiles   > 0 ? $this->error("  ✗ Order files on local disk : {$localFiles}") : $this->info("  ✓ No order files on local disk");
        $localReports > 0 ? $this->error("  ✗ Reports on local disk     : {$localReports}") : $this->info("  ✓ No reports on local disk");
        $this->line("  R2 order files  : {$r2Files}");
        $this->line("  R2 report files : {$r2Reports}");
        $this->newLine();

        // CHECK 2 - Client Relationships
        $this->info('CHECK 2: Client Relationships');
        $noClient     = User::where('role', 'client')->whereNull('client_id')->count();
        $brokenClient = User::where('role', 'client')
                            ->whereNotNull('client_id')
                            ->whereDoesntHave('client')->count();
        $noClient     > 0 ? $this->error("  ✗ Client users with no client_id   : {$noClient}") : $this->info("  ✓ All client users have client_id");
        $brokenClient > 0 ? $this->error("  ✗ Client users with broken client  : {$brokenClient}") : $this->info("  ✓ All client relationships intact");
        $this->newLine();

        // CHECK 3 - Sessions
        $this->info('CHECK 3: Sessions');
        $totalSessions = DB::table('sessions')->count();
        $staleSessions = DB::table('sessions')
                            ->where('last_activity', '<', now()->subHours(2)->timestamp)
                            ->count();
        $this->line("  Total sessions : {$totalSessions}");
        $staleSessions > 50 ? $this->warn("  ⚠ Stale sessions (>2hrs) : {$staleSessions} — consider truncating") : $this->info("  ✓ Stale sessions : {$staleSessions}");
        $this->newLine();

        // CHECK 4 - Queue Jobs
        $this->info('CHECK 4: Queue Jobs');
        $pendingJobs = DB::table('jobs')->count();
        $failedJobs  = DB::table('failed_jobs')->count();
        $pendingJobs > 10 ? $this->warn("  ⚠ Pending jobs : {$pendingJobs} — queue worker may not be running") : $this->info("  ✓ Pending jobs : {$pendingJobs}");
        $failedJobs  > 0  ? $this->error("  ✗ Failed jobs  : {$failedJobs}") : $this->info("  ✓ No failed jobs");
        if ($failedJobs > 0) {
            $latest = DB::table('failed_jobs')->orderByDesc('failed_at')->first();
            if ($latest) {
                $this->error('  Latest failure : ' . substr($latest->exception, 0, 200));
            }
        }
        $this->newLine();

        // CHECK 5 - Config Values
        $this->info('CHECK 5: Config / Services');
        $payoutRate   = config('services.portal.vendor_payout_per_order');
        $slaMinutes   = config('services.portal.default_sla_minutes');
        $clientPrice  = config('services.portal.default_client_price');
        $payoutRate  === null ? $this->error('  ✗ vendor_payout_per_order : NULL — will crash CloseDayCommand') : $this->info("  ✓ vendor_payout_per_order : {$payoutRate}");
        $slaMinutes  === null ? $this->error('  ✗ default_sla_minutes     : NULL — will crash order creation') : $this->info("  ✓ default_sla_minutes     : {$slaMinutes}");
        $clientPrice === null ? $this->warn('  ⚠ default_client_price    : NULL — fallback missing in CloseDayCommand') : $this->info("  ✓ default_client_price    : {$clientPrice}");
        $this->newLine();

        // CHECK 6 - Database
        $this->info('CHECK 6: Database Connection');
        try {
            DB::connection()->getPdo();
            $this->info('  ✓ DB connection : OK');
            $this->line('  DB name         : ' . DB::connection()->getDatabaseName());
            $this->line('  Total orders    : ' . Order::count());
            $this->line('  Total users     : ' . User::count());
            $this->line('  Total clients   : ' . Client::count());
        } catch (\Exception $e) {
            $this->error('  ✗ DB FAILED : ' . $e->getMessage());
        }
        $this->newLine();

        // CHECK 7 - Null / Broken Data
        $this->info('CHECK 7: Data Integrity');
        $ordersNoClient    = Order::whereDoesntHave('client')->count();
        $deliveredNoReport = Order::where('status', 'delivered')->whereDoesntHave('report')->count();
        $ordersNoClient    > 0 ? $this->error("  ✗ Orders with no client     : {$ordersNoClient}") : $this->info('  ✓ All orders have a client');
        $deliveredNoReport > 0 ? $this->warn("  ⚠ Delivered orders no report: {$deliveredNoReport}") : $this->info('  ✓ All delivered orders have reports');
        $this->newLine();

        if ($deliveredNoReport > 0) {
            $sampleIds = Order::where('status', 'delivered')
                ->whereDoesntHave('report')
                ->orderBy('id')
                ->limit(5)
                ->pluck('id')
                ->implode(', ');
            $this->line('  Sample order IDs : ' . ($sampleIds ?: 'n/a'));
            $this->line('  Hint: run `php artisan app:repair-missing-reports --limit=10`');
        }

        // CHECK 8 - R2 Live Test
        $this->info('CHECK 8: R2 Live Connection Test');
        try {
            \Illuminate\Support\Facades\Storage::disk('r2')->put('health-check/test.txt', 'ok-' . now());
            $read = \Illuminate\Support\Facades\Storage::disk('r2')->get('health-check/test.txt');
            \Illuminate\Support\Facades\Storage::disk('r2')->delete('health-check/test.txt');
            str_starts_with($read, 'ok-') ? $this->info('  ✓ R2 write/read/delete : OK') : $this->warn('  ⚠ R2 read returned unexpected content');
        } catch (\Throwable $e) {
            $this->error('  ✗ R2 FAILED : ' . $e->getMessage());
        }
        $this->newLine();

        $this->info('========================================');
        $this->info('           CHECK COMPLETE               ');
        $this->info('========================================');

        return Command::SUCCESS;
    }
}
