<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\OrderFile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PurgeOrderFilesCommand extends Command
{
    protected $signature   = 'app:purge-order-files';
    protected $description = 'Delete stored files from disk for all orders (runs nightly at 2 AM)';

    public function handle(): int
    {
        $orders = Order::has('files')->get();

        $deletedFiles  = 0;
        $deletedOrders = 0;

        foreach ($orders as $order) {
            foreach ($order->files as $file) {
                Storage::delete($file->file_path);
                $file->delete();
                $deletedFiles++;
            }

            Storage::deleteDirectory('orders/' . $order->id);
            $deletedOrders++;
        }

        $this->info("Purged {$deletedFiles} file(s) across {$deletedOrders} order(s).");

        return self::SUCCESS;
    }
}
