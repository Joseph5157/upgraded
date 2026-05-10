<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Models\Order;
use App\Support\StorageLifecycle;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CleanupLinkOrdersCommand extends Command
{
    protected $signature   = 'app:cleanup-link-orders {--hours=24 : Delete link orders older than this many hours}';
    protected $description = 'Delete orders submitted via upload links that are older than the specified age, and reset client slots';

    public function handle(): int
    {
        $hours = (int) $this->option('hours');

        $orders = Order::where('source', 'link')
            ->where('created_at', '<', now()->subHours($hours))
            ->with(['files', 'report'])
            ->get();

        if ($orders->isEmpty()) {
            $this->info("No link orders older than {$hours} hour(s) found.");
            return self::SUCCESS;
        }

        $deletedOrders = 0;
        $deletedFiles  = 0;

        foreach ($orders as $order) {
            DB::transaction(function () use ($order, &$deletedFiles) {
                // Delete uploaded files from storage
                foreach ($order->files as $file) {
                    StorageLifecycle::deleteStoredFileIfPresent($file->disk ?: 'r2', $file->file_path);
                    $file->delete();
                    $deletedFiles++;
                }

                // Delete the whole order directory
                collect(StorageLifecycle::uniqueDisks($order->files->pluck('disk')->all(), 'r2'))
                    ->each(fn ($disk) => StorageLifecycle::deleteStoredDirectory($disk, 'orders/' . $order->id));

                // Delete report files if any
                if ($order->report) {
                    StorageLifecycle::deleteStoredFileIfPresent($order->report->ai_report_disk ?: 'r2', $order->report->ai_report_path);
                    StorageLifecycle::deleteStoredFileIfPresent($order->report->plag_report_disk ?: 'r2', $order->report->plag_report_path);
                    $order->report->delete();
                }

                // Restore the slot credit atomically inside this transaction so a
                // mid-loop crash cannot leave the order deleted but its slot unreturned.
                $fileCount = $order->files_count;
                if ($fileCount > 0) {
                    Client::where('id', $order->client_id)
                        ->lockForUpdate()
                        ->first()
                        ?->decrement('slots_consumed', $fileCount);
                }

                $order->delete();
            });

            $deletedOrders++;
        }

        $this->info("Cleaned up {$deletedOrders} link order(s) and {$deletedFiles} file(s) older than {$hours} hour(s).");
        Log::info("CleanupLinkOrders: removed {$deletedOrders} orders, {$deletedFiles} files.", [
            'hours' => $hours,
        ]);

        return self::SUCCESS;
    }
}
