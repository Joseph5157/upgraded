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
        $released = Order::where('status', OrderStatus::Processing)
            ->whereNotNull('claimed_by')
            ->where('due_at', '<', now())
            ->update([
                'claimed_by' => null,
                'claimed_at' => null,
                'status'     => OrderStatus::Pending,
            ]);

        if ($released > 0) {
            $this->info("Released {$released} overdue order(s) back to the pending pool.");
        } else {
            $this->info('No overdue orders to release.');
        }

        return Command::SUCCESS;
    }
}
