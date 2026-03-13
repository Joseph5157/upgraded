<?php

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Console\Command;

class AutoReleaseOrdersCommand extends Command
{
    protected $signature   = 'orders:auto-release';
    protected $description = 'Release overdue claimed orders back to the pending pool.';

    public function handle(): int
    {
        // Fetch individually so we can increment release_count per order.
        // release_count > 0 means a vendor already submitted to Turnitin —
        // the client is not eligible for an automatic credit-slot refund.
        $orders = Order::where('status', OrderStatus::Processing)
            ->whereNotNull('claimed_by')
            ->where('due_at', '<', now())
            ->get();

        foreach ($orders as $order) {
            $order->update([
                'claimed_by'    => null,
                'claimed_at'    => null,
                'status'        => OrderStatus::Pending,
                'release_count' => $order->release_count + 1,
            ]);
        }

        $released = $orders->count();
        $released > 0
            ? $this->info("Released {$released} overdue order(s) back to the pending pool.")
            : $this->info('No overdue orders to release.');

        return Command::SUCCESS;
    }
}
