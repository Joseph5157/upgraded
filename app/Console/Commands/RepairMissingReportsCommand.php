<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\OrderReport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class RepairMissingReportsCommand extends Command
{
    protected $signature = 'app:repair-missing-reports
                            {--apply : Persist fixes to the database}
                            {--limit=50 : Max number of orders to scan}
                            {--allow-ai-skip : If AI report is missing, set a skip reason}
                            {--disk=r2 : Storage disk to scan for report files}';

    protected $description = 'Find delivered orders without report rows and rebuild them from storage';

    public function handle(): int
    {
        $disk = (string) $this->option('disk');
        $apply = (bool) $this->option('apply');
        $allowAiSkip = (bool) $this->option('allow-ai-skip');
        $limit = (int) $this->option('limit');

        $missing = Order::where('status', 'delivered')
            ->whereDoesntHave('report')
            ->orderBy('id')
            ->limit($limit)
            ->get();

        if ($missing->isEmpty()) {
            $this->info('No delivered orders are missing reports.');
            return Command::SUCCESS;
        }

        $this->line('Scanning '.count($missing).' delivered orders without reports.');

        $fixed = 0;
        $skipped = 0;

        foreach ($missing as $order) {
            $aiPath = $this->pickLatestFile($disk, 'reports/'.$order->id.'/ai');
            $plagPath = $this->pickLatestFile($disk, 'reports/'.$order->id.'/plag');

            if (!$plagPath) {
                $this->warn("Order #{$order->id}: no plagiarism report found on {$disk}.");
                $skipped++;
                continue;
            }

            if (!$aiPath && !$allowAiSkip) {
                $this->warn("Order #{$order->id}: no AI report found on {$disk} (use --allow-ai-skip to set a skip reason).");
                $skipped++;
                continue;
            }

            $aiSkipReason = null;
            if (!$aiPath && $allowAiSkip) {
                $aiSkipReason = 'Auto repair: AI report file missing at time of repair.';
            }

            if ($apply) {
                OrderReport::updateOrCreate(
                    ['order_id' => $order->id],
                    [
                        'ai_report_path'   => $aiPath,
                        'ai_report_disk'   => $disk,
                        'ai_skip_reason'   => $aiSkipReason,
                        'plag_report_path' => $plagPath,
                        'plag_report_disk' => $disk,
                    ]
                );
                $this->info("Order #{$order->id}: report row repaired.");
            } else {
                $this->line("Order #{$order->id}: would repair (AI: ".($aiPath ?: 'none').", Plag: {$plagPath}).");
            }

            $fixed++;
        }

        $summary = $apply ? 'Repaired' : 'Would repair';
        $this->line("{$summary}: {$fixed}. Skipped: {$skipped}.");

        if (!$apply) {
            $this->line('Run again with --apply to persist changes.');
        }

        return Command::SUCCESS;
    }

    private function pickLatestFile(string $disk, string $dir): ?string
    {
        $files = Storage::disk($disk)->allFiles($dir);
        if (empty($files)) {
            return null;
        }

        $latestPath = null;
        $latestTs = null;

        foreach ($files as $path) {
            try {
                $ts = Storage::disk($disk)->lastModified($path);
            } catch (\Throwable $e) {
                $ts = null;
            }

            if ($latestTs === null || ($ts !== null && $ts >= $latestTs)) {
                $latestTs = $ts;
                $latestPath = $path;
            }
        }

        return $latestPath;
    }
}
