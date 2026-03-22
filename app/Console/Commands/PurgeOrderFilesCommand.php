<?php

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderFile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PurgeOrderFilesCommand extends Command
{
    protected $signature   = 'app:purge-order-files {--days=1 : Only purge files from orders delivered more than this many days ago}';
    protected $description = 'Delete stored files from disk for delivered orders older than the specified age';

    public function handle(): int
    {
        $days = (int) $this->option('days');

        $orders = Order::where('status', OrderStatus::Delivered)
            ->where('delivered_at', '<', now()->subDays($days))
            ->has('files')
            ->with(['files', 'report'])
            ->get();

        $deletedFiles  = 0;
        $deletedOrders = 0;

        foreach ($orders as $order) {
            // Delete client-uploaded files
            foreach ($order->files as $file) {
                Storage::disk($file->disk ?: 'r2')->delete($file->file_path);
                $file->delete();
                $deletedFiles++;
            }
            foreach ($order->files->pluck('disk')->filter()->push('r2')->unique() as $disk) {
                Storage::disk($disk)->deleteDirectory('orders/' . $order->id);
            }

            // Delete report PDFs (AI + Plag)
            if ($order->report) {
                if ($order->report->ai_report_path) {
                    Storage::disk($order->report->ai_report_disk ?: 'r2')->delete($order->report->ai_report_path);
                }
                if ($order->report->plag_report_path) {
                    Storage::disk($order->report->plag_report_disk ?: 'r2')->delete($order->report->plag_report_path);
                }
                collect([$order->report->ai_report_disk, $order->report->plag_report_disk, 'r2'])
                    ->filter()
                    ->unique()
                    ->each(fn ($disk) => Storage::disk($disk)->deleteDirectory('reports/' . $order->id));
            }

            $deletedOrders++;
        }

        $this->info("Purged {$deletedFiles} file(s) across {$deletedOrders} delivered order(s) older than {$days} day(s).");

        return self::SUCCESS;
    }
}
