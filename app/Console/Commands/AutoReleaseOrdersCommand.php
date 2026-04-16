<?php

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AutoReleaseOrdersCommand extends Command
{
    protected $signature   = 'orders:auto-release';
    protected $description = 'Release overdue claimed orders back to the pending pool.';

    public function handle(): int
    {
        $now = now();

        // Collect the data we need for audit logs BEFORE the bulk update wipes
        // claimed_by, then perform all writes in a single atomic transaction.
        // Previously: individual ->update() per row (N+1 queries) with no
        // transaction and no audit trail.

        // --- Processing orders: overdue and still claimed ---
        // release_count is incremented so the client knows work was attempted.
        $processingRows = Order::where('status', OrderStatus::Processing)
            ->whereNotNull('claimed_by')
            ->where('due_at', '<', $now)
            ->get(['id', 'claimed_by', 'status']);

        // --- Stuck Pending orders: claimed but vendor never started ---
        $stuckPendingRows = Order::where('status', OrderStatus::Pending)
            ->whereNotNull('claimed_by')
            ->where('due_at', '<', $now)
            ->get(['id', 'claimed_by', 'status']);

        $processingIds   = $processingRows->pluck('id')->all();
        $stuckPendingIds = $stuckPendingRows->pluck('id')->all();
        $allIds          = array_merge($processingIds, $stuckPendingIds);

        if (empty($allIds)) {
            $this->info('No overdue orders to release.');
            return Command::SUCCESS;
        }

        DB::transaction(function () use ($processingIds, $stuckPendingIds, $processingRows, $stuckPendingRows, $now) {
            // Bulk-update processing orders: unclaim + revert to pending + bump counter.
            if (! empty($processingIds)) {
                Order::whereIn('id', $processingIds)->update([
                    'claimed_by'    => null,
                    'claimed_at'    => null,
                    'status'        => OrderStatus::Pending,
                    'release_count' => DB::raw('release_count + 1'),
                    'updated_at'    => $now,
                ]);
            }

            // Bulk-update stuck-pending orders: just remove the claim lock.
            if (! empty($stuckPendingIds)) {
                Order::whereIn('id', $stuckPendingIds)->update([
                    'claimed_by' => null,
                    'claimed_at' => null,
                    'updated_at' => $now,
                ]);
            }

            // Write one audit-log row per released order.
            // user_id = null signals a system/automated action.
            $logRows = [];

            foreach ($processingRows as $row) {
                $logRows[] = [
                    'order_id'   => $row->id,
                    'user_id'    => null,
                    'action'     => 'auto_release',
                    'old_status' => OrderStatus::Processing->value,
                    'new_status' => OrderStatus::Pending->value,
                    'notes'      => 'Auto-released: SLA exceeded while in processing (was claimed by user #' . $row->claimed_by . ').',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            foreach ($stuckPendingRows as $row) {
                $logRows[] = [
                    'order_id'   => $row->id,
                    'user_id'    => null,
                    'action'     => 'auto_release',
                    'old_status' => OrderStatus::Pending->value,
                    'new_status' => OrderStatus::Pending->value,
                    'notes'      => 'Auto-released: claim lock removed after SLA expiry (was claimed by user #' . $row->claimed_by . ').',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            // Single bulk insert for all audit rows.
            DB::table('order_logs')->insert($logRows);
        });

        $released = count($processingIds) + count($stuckPendingIds);
        $this->info("Released {$released} overdue order(s) back to the pending pool.");

        return Command::SUCCESS;
    }
}
