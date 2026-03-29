<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\DeleteClientOrderService;
use Illuminate\Console\Command;

class DeleteOrdersCommand extends Command
{
    protected $signature = 'app:delete-orders
                            {ids* : One or more order IDs to delete}
                            {--dry-run : Show what would be deleted without making changes}';

    protected $description = 'Delete orders using the app cleanup service so storage and DB stay in sync';

    public function handle(DeleteClientOrderService $deleteOrderService): int
    {
        $ids = collect($this->argument('ids'))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            $this->error('No valid order IDs were provided.');
            return Command::FAILURE;
        }

        $orders = Order::with(['files', 'report', 'client'])
            ->whereIn('id', $ids)
            ->orderBy('id')
            ->get()
            ->keyBy('id');

        $missing = $ids->diff($orders->keys()->values());
        foreach ($missing as $id) {
            $this->warn("Order #{$id} was not found.");
        }

        if ($orders->isEmpty()) {
            $this->warn('No matching orders found.');
            return Command::SUCCESS;
        }

        foreach ($orders as $order) {
            if (!$order->client) {
                $this->warn("Order #{$order->id} has no client relation. Skipping.");
                continue;
            }

            if ($this->option('dry-run')) {
                $this->line("Would delete order #{$order->id} (status: {$order->status->value}, client_id: {$order->client_id}).");
                continue;
            }

            $deleteOrderService->execute($order, $order->client);
            $this->info("Deleted order #{$order->id}.");
        }

        return Command::SUCCESS;
    }
}
