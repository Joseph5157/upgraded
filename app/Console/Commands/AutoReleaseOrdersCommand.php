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

        // --- Stuck pending orders: claimed but never moved to processing after 1 hour ---
        $stuckPendingOrders = Order::whereIn('status', [OrderStatus::Pending, OrderStatus::Claimed])
            ->whereNotNull('claimed_by')
            ->where('claimed_at', '<', now()->subHour())
            ->whereNotIn('id', $processingOrders->pluck('id'))
            ->get();

        foreach ($stuckPendingOrders as $order) {
            $order->update([
                'claimed_by' => null,
                'claimed_at' => null,
                'status'     => OrderStatus::Pending,
            ]);
        }

        // Release orders where vendor has held the claim for more than 30 minutes
        // regardless of whether the client deadline has passed or not.
        $exceededClaimWindow = Order::whereIn('status', [OrderStatus::Pending, OrderStatus::Claimed, OrderStatus::Processing])
            ->whereNotNull('claimed_by')
            ->where('claimed_at', '<', now()->subMinutes(30))
            ->whereNotIn('id', $processingOrders->pluck('id'))
            ->whereNotIn('id', $stuckPendingOrders->pluck('id'))
            ->get();

        foreach ($exceededClaimWindow as $order) {
            $order->update([
                'claimed_by'    => null,
                'claimed_at'    => null,
                'status'        => OrderStatus::Pending,
                'release_count' => $order->release_count + 1,
            ]);
        }

        $released = $processingOrders->count() + $stuckPendingOrders->count() + $exceededClaimWindow->count();

        if ($processingOrders->count() > 0 || $stuckPendingOrders->count() > 0) {
            $this->info("Released {$released} overdue order(s) past client deadline back to pool.");
        }
        if ($exceededClaimWindow->count() > 0) {
            $this->info("Released {$exceededClaimWindow->count()} order(s) that exceeded the 30-minute vendor claim window.");
        }
        if ($released === 0) {
            $this->info('No orders to release.');
        }

        return Command::SUCCESS;
    }
}
