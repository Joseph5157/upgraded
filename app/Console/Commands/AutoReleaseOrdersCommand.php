<?php

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Console\Command;

class AutoReleaseOrdersCommand extends Command
{
    protected $signature   = 'orders:auto-release';
    protected $description = 'Release claimed orders that have exceeded their allowed hold windows back to the pending pool.';

    public function handle(): int
    {
        // --- Processing orders: vendor started work but has held for over 2 hours ---
        // claimed_at is refreshed when work begins (startProcessing), so this 2-hour
        // window starts from when the vendor actually downloaded the file.
        $processingOrders = Order::where('status', OrderStatus::Processing)
            ->whereNotNull('claimed_by')
            ->where('claimed_at', '<', now()->subHours(2))
            ->get();

        foreach ($processingOrders as $order) {
            $order->update([
                'claimed_by'    => null,
                'claimed_at'    => null,
                'status'        => OrderStatus::Pending,
                'release_count' => $order->release_count + 1,
            ]);
        }

        // --- Stuck pending/claimed orders: claimed but never started processing after 30 minutes ---
        // Processing orders are intentionally excluded — they are handled by the 2-hour window above.
        $stuckOrders = Order::whereIn('status', [OrderStatus::Pending, OrderStatus::Claimed])
            ->whereNotNull('claimed_by')
            ->where('claimed_at', '<', now()->subMinutes(30))
            ->whereNotIn('id', $processingOrders->pluck('id'))
            ->get();

        foreach ($stuckOrders as $order) {
            $order->update([
                'claimed_by'    => null,
                'claimed_at'    => null,
                'status'        => OrderStatus::Pending,
                'release_count' => $order->release_count + 1,
            ]);
        }

        $released = $processingOrders->count() + $stuckOrders->count();

        if ($processingOrders->count() > 0) {
            $this->info("Released {$processingOrders->count()} processing order(s) that exceeded the 2-hour work window.");
        }
        if ($stuckOrders->count() > 0) {
            $this->info("Released {$stuckOrders->count()} stuck order(s) that were claimed but never started within 30 minutes.");
        }
        if ($released === 0) {
            $this->info('No orders to release.');
        }

        return Command::SUCCESS;
    }
}
