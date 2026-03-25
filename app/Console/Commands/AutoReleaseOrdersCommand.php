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
        // Release overdue PROCESSING orders back to the pending pool.
        // release_count > 0 means a vendor already submitted to Turnitin —
        // the client is not eligible for an automatic credit-slot refund.
        $processingOrders = Order::where('status', OrderStatus::Processing)
            ->whereNotNull('claimed_by')
            ->where('due_at', '<', now())
            ->get();

        foreach ($processingOrders as $order) {
            $order->update([
                'claimed_by'    => null,
                'claimed_at'    => null,
                'status'        => OrderStatus::Pending,
                'release_count' => $order->release_count + 1,
            ]);
        }

        // Also release PENDING orders that were claimed but the vendor never started
        // processing before the SLA expired — these would be stuck forever otherwise.
        $stuckPendingOrders = Order::where('status', OrderStatus::Pending)
            ->whereNotNull('claimed_by')
            ->where('due_at', '<', now())
            ->get();

        foreach ($stuckPendingOrders as $order) {
            $order->update([
                'claimed_by' => null,
                'claimed_at' => null,
                // status stays Pending — just remove the claim lock
            ]);
        }

        $released = $processingOrders->count() + $stuckPendingOrders->count();
        $released > 0
            ? $this->info("Released {$released} overdue order(s) back to the pending pool.")
            : $this->info('No overdue orders to release.');

        return Command::SUCCESS;
    }
}
