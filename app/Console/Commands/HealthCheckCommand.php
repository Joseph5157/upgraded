<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Models\Order;
use App\Models\OrderFile;
use App\Models\OrderReport;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

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

        $this->info('CHECK 1: R2 / Storage Files');
        $this->line('  FILESYSTEM_DISK    : '.config('filesystems.default'));
        $this->line('  R2_BUCKET          : '.env('R2_BUCKET'));
        $this->line('  R2_ENDPOINT        : '.env('R2_ENDPOINT'));
        $localFiles = OrderFile::where('disk', 'local')->orWhereNull('disk')->count();
        $r2Files = OrderFile::where('disk', 'r2')->count();
        $localReports = OrderReport::where('ai_report_disk', 'local')
            ->orWhere('plag_report_disk', 'local')
            ->count();
        $r2Reports = OrderReport::where('ai_report_disk', 'r2')->count();
        $localFiles > 0
            ? $this->error("  x Order files on local disk : {$localFiles}")
            : $this->info('  OK No order files on local disk');
        $localReports > 0
            ? $this->error("  x Reports on local disk     : {$localReports}")
            : $this->info('  OK No reports on local disk');
        $this->line("  R2 order files  : {$r2Files}");
        $this->line("  R2 report files : {$r2Reports}");
        $this->newLine();

        $this->info('CHECK 2: Client Relationships');
        $noClient = User::where('role', 'client')->whereNull('client_id')->count();
        $brokenClient = User::where('role', 'client')
            ->whereNotNull('client_id')
            ->whereDoesntHave('client')
            ->count();
        $noClient > 0
            ? $this->error("  x Client users with no client_id  : {$noClient}")
            : $this->info('  OK All client users have client_id');
        $brokenClient > 0
            ? $this->error("  x Client users with broken client : {$brokenClient}")
            : $this->info('  OK All client relationships intact');
        $this->newLine();

        $this->info('CHECK 3: Sessions');
        $this->line('  Session driver : '.config('session.driver'));
        $this->line('  Session store  : '.(config('session.store') ?? 'n/a'));
        $this->line('  Session conn   : '.(config('session.connection') ?? 'n/a'));
        if (config('session.driver') === 'database') {
            $totalSessions = DB::table('sessions')->count();
            $staleSessions = DB::table('sessions')
                ->where('last_activity', '<', now()->subHours(2)->timestamp)
                ->count();
            $this->line("  Total sessions : {$totalSessions}");
            $staleSessions > 50
                ? $this->warn("  ! Stale sessions (>2hrs) : {$staleSessions}")
                : $this->info("  OK Stale sessions : {$staleSessions}");
        } else {
            $this->line('  Session table  : skipped because sessions are not database-backed');
        }
        $this->newLine();

        $this->info('CHECK 4: Redis / Queue');
        try {
            Redis::connection()->ping();
            $this->info('  OK Redis connection : OK');
        } catch (\Throwable $e) {
            $this->error('  x Redis connection : FAILED - '.$e->getMessage());
        }

        $this->line('  Queue default      : '.config('queue.default'));
        $this->line('  Redis queue name   : '.env('REDIS_QUEUE', 'default'));
        $this->line('  Redis retry_after  : '.config('queue.connections.redis.retry_after'));
        $this->line('  Redis block_for    : '.var_export(config('queue.connections.redis.block_for'), true));

        try {
            $pendingJobs = Queue::connection('redis')->size(env('REDIS_QUEUE', 'default'));
            $this->line("  Redis pending jobs : {$pendingJobs}");
        } catch (\Throwable $e) {
            $this->warn('  ! Redis queue size unavailable - '.$e->getMessage());
        }

        $failedJobs = DB::table('failed_jobs')->count();
        $failedJobs > 0
            ? $this->error("  x Failed jobs        : {$failedJobs}")
            : $this->info('  OK No failed jobs');
        if ($failedJobs > 0) {
            $latest = DB::table('failed_jobs')->orderByDesc('failed_at')->first();
            if ($latest) {
                $this->error('  Latest failure     : '.substr($latest->exception, 0, 200));
            }
        }
        $this->newLine();

        $this->info('CHECK 5: Config / Services');
        $payoutRate = config('services.portal.vendor_payout_per_order');
        $slaMinutes = config('services.portal.default_sla_minutes');
        $clientPrice = config('services.portal.default_client_price');
        $payoutRate === null
            ? $this->error('  x vendor_payout_per_order : NULL')
            : $this->info("  OK vendor_payout_per_order : {$payoutRate}");
        $slaMinutes === null
            ? $this->error('  x default_sla_minutes     : NULL')
            : $this->info("  OK default_sla_minutes     : {$slaMinutes}");
        $clientPrice === null
            ? $this->warn('  ! default_client_price    : NULL')
            : $this->info("  OK default_client_price    : {$clientPrice}");
        $this->newLine();

        $this->info('CHECK 6: Database Connection');
        try {
            DB::connection()->getPdo();
            $this->info('  OK DB connection : OK');
            $this->line('  DB name          : '.DB::connection()->getDatabaseName());
            $this->line('  Total orders     : '.Order::count());
            $this->line('  Total users      : '.User::count());
            $this->line('  Total clients    : '.Client::count());
        } catch (\Throwable $e) {
            $this->error('  x DB FAILED : '.$e->getMessage());
        }
        $this->newLine();

        $this->info('CHECK 7: Data Integrity');
        $ordersNoClient = Order::whereDoesntHave('client')->count();
        $deliveredNoReport = Order::where('status', 'delivered')->whereDoesntHave('report')->count();
        $ordersNoClient > 0
            ? $this->error("  x Orders with no client      : {$ordersNoClient}")
            : $this->info('  OK All orders have a client');
        $deliveredNoReport > 0
            ? $this->warn("  ! Delivered orders no report : {$deliveredNoReport}")
            : $this->info('  OK All delivered orders have reports');
        $this->newLine();

        if ($deliveredNoReport > 0) {
            $sampleIds = Order::where('status', 'delivered')
                ->whereDoesntHave('report')
                ->orderBy('id')
                ->limit(5)
                ->pluck('id')
                ->implode(', ');
            $this->line('  Sample order IDs : '.($sampleIds ?: 'n/a'));
            $this->line('  Hint: run `php artisan app:repair-missing-reports --limit=10`');
        }

        $this->info('CHECK 8: R2 Live Connection Test');
        try {
            Storage::disk('r2')->put('health-check/test.txt', 'ok-'.now());
            $read = Storage::disk('r2')->get('health-check/test.txt');
            Storage::disk('r2')->delete('health-check/test.txt');
            str_starts_with($read, 'ok-')
                ? $this->info('  OK R2 write/read/delete : OK')
                : $this->warn('  ! R2 read returned unexpected content');
        } catch (\Throwable $e) {
            $this->error('  x R2 FAILED : '.$e->getMessage());
        }
        $this->newLine();

        $this->info('========================================');
        $this->info('           CHECK COMPLETE               ');
        $this->info('========================================');

        return Command::SUCCESS;
    }
}
