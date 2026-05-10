<?php

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderFile;
use App\Support\StorageLifecycle;
use Illuminate\Console\Command;

class PurgeOrderFilesCommand extends Command
{
    protected $signature   = 'app:purge-order-files {--days=1 : Only purge files from orders delivered more than this many days ago}';
    protected $description = 'Delete stored files from disk for delivered orders older than the specified age';

    public function handle(): int
    {
        $days = (int) $this->option('days');

        $orders = Order::where('status', OrderStatus::Delivered)
            ->where('delivered_at', '<', now()->subDays($days))
            ->where('is_downloaded', true)
            ->with(['files', 'report'])
            ->get();

        $deletedFiles  = 0;
        $deletedOrders = 0;

        foreach ($orders as $order) {
            // Delete client-uploaded files
            foreach ($order->files as $file) {
                StorageLifecycle::deleteStoredFileIfPresent($file->disk ?: 'r2', $file->file_path);
                $file->delete();
                $deletedFiles++;
            }
            foreach (StorageLifecycle::uniqueDisks($order->files->pluck('disk')->all(), 'r2') as $disk) {
                StorageLifecycle::deleteStoredDirectory($disk, 'orders/' . $order->id);
            }

            // Delete report PDFs (AI + Plag)
            if ($order->report) {
                StorageLifecycle::deleteStoredFileIfPresent($order->report->ai_report_disk ?: 'r2', $order->report->ai_report_path);
                StorageLifecycle::deleteStoredFileIfPresent($order->report->plag_report_disk ?: 'r2', $order->report->plag_report_path);
                foreach (StorageLifecycle::uniqueDisks([
                    $order->report->ai_report_disk,
                    $order->report->plag_report_disk,
                ], 'r2') as $disk) {
                    StorageLifecycle::deleteStoredDirectory($disk, 'reports/' . $order->id);
                }
            }

            $order->files()->delete();
            $order->report()->delete();
            $order->delete();

            $deletedOrders++;
        }

        $this->info("Purged {$deletedFiles} file(s) across {$deletedOrders} delivered order(s) older than {$days} day(s).");

        return self::SUCCESS;
    }
}
