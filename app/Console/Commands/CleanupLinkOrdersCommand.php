<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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

        // Group by client to batch-update slots_consumed
        $slotDeductions = [];

        foreach ($orders as $order) {
            DB::transaction(function () use ($order, &$deletedFiles, &$slotDeductions) {
                // Delete uploaded files from storage
                foreach ($order->files as $file) {
                    Storage::disk($file->disk ?: 'r2')->delete($file->file_path);
                    $file->delete();
                    $deletedFiles++;
                }

                // Delete the whole order directory
                collect($order->files->pluck('disk')->filter()->push('r2')->unique())
                    ->each(fn ($disk) => Storage::disk($disk)->deleteDirectory('orders/' . $order->id));

                // Delete report files if any
                if ($order->report) {
                    if ($order->report->ai_report_path) {
                        Storage::disk($order->report->ai_report_disk ?: 'r2')->delete($order->report->ai_report_path);
                    }
                    if ($order->report->plag_report_path) {
                        Storage::disk($order->report->plag_report_disk ?: 'r2')->delete($order->report->plag_report_path);
                    }
                    $order->report->delete();
                }

                // Track slots to restore per client
                $slotDeductions[$order->client_id] = ($slotDeductions[$order->client_id] ?? 0) + $order->files_count;

                $order->delete();
            });

            $deletedOrders++;
        }

        // Restore slots_consumed on each client so links are reusable
        foreach ($slotDeductions as $clientId => $fileCount) {
            Client::where('id', $clientId)->decrement('slots_consumed', $fileCount);
        }

        $this->info("Cleaned up {$deletedOrders} link order(s) and {$deletedFiles} file(s) older than {$hours} hour(s).");
        Log::info("CleanupLinkOrders: removed {$deletedOrders} orders, {$deletedFiles} files.", [
            'hours'  => $hours,
            'client_ids' => array_keys($slotDeductions),
        ]);

        return self::SUCCESS;
    }
}
