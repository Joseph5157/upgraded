<?php

namespace App\Services\Finance;

use App\Models\Order;
use App\Models\User;
use App\Models\VendorEarningTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * VendorEarningService
 *
 * Every method that mutates users.pending_earning_balance or
 * users.approved_payable_balance MUST be called:
 *   (a) Inside an active DB::transaction(), AND
 *   (b) With the vendor User instance retrieved via lockForUpdate().
 *
 * Methods wrap their own DB::transaction() so they are safe to call
 * standalone. When called from within an existing transaction (e.g.
 * from OrderWorkflowService::markDelivered), the inner transaction
 * becomes a savepoint — fully atomic with the outer operation.
 *
 * Status flow:
 *   pending_order_earning (posted)  → approve_earning (posted)   on admin approval
 *   pending_order_earning (posted)  → reversal (posted)          on rejection
 *   approve_earning (posted)        → reversal (posted)          on post-approval rejection
 */
class VendorEarningService
{
    /**
     * Create a pending earning transaction when a vendor's report is delivered.
     *
     * Rules:
     *  - Derives vendor from order.claimed_by. Returns null if no vendor assigned.
     *  - Idempotent: returns null if a posted pending_order_earning already exists
     *    for this order (prevents double-earning on repeated calls).
     *  - Stores a rate snapshot on the order (vendor_rate_per_file, vendor_amount).
     *  - Increases users.pending_earning_balance only.
     *  - Does NOT touch approved_payable_balance.
     *  - Does NOT touch slots or slots_consumed.
     *  - Must be called inside an active DB::transaction() when used as part
     *    of a larger atomic operation (e.g. inside markDelivered).
     *
     * @param  Order      $order      The delivered order (should be locked by caller)
     * @param  User|null  $createdBy  Actor triggering the earning (vendor or admin)
     * @return VendorEarningTransaction|null  Null if skipped (no vendor or already earned)
     */
    public function createPendingForOrder(Order $order, ?User $createdBy = null): ?VendorEarningTransaction
    {
        return DB::transaction(function () use ($order, $createdBy): ?VendorEarningTransaction {
            // Re-read and lock the order row inside this transaction.
            $lockedOrder = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            // No vendor assigned → no earning to create.
            if ($lockedOrder->claimed_by === null) {
                Log::info('VendorEarningService: no vendor assigned to order #' . $lockedOrder->id . ' — pending earning skipped.');
                return null;
            }

            // Idempotency: if a posted pending earning already exists for this order, skip.
            $alreadyExists = VendorEarningTransaction::where('order_id', $lockedOrder->id)
                ->where('type', VendorEarningTransaction::TYPE_PENDING_ORDER_EARNING)
                ->where('status', VendorEarningTransaction::STATUS_POSTED)
                ->exists();

            if ($alreadyExists) {
                return null;
            }

            // Lock the vendor row to prevent concurrent balance updates.
            $vendor = User::whereKey($lockedOrder->claimed_by)->lockForUpdate()->firstOrFail();

            // Rate snapshot from vendor's current payout_rate.
            $ratePerFile = (float) ($vendor->payout_rate ?? 0);

            // File count: use order.files_count → fallback to credits_consumed → fallback to 1.
            $filesCount = (int) max(1, $lockedOrder->files_count ?? $lockedOrder->credits_consumed ?? 1);

            $vendorAmount = round($filesCount * $ratePerFile, 2);

            // Store financial snapshot on the order.
            $lockedOrder->update([
                'vendor_rate_per_file' => $ratePerFile,
                'vendor_amount'        => $vendorAmount,
            ]);

            // Calculate new pending balance.
            $currentPending  = (float) ($vendor->pending_earning_balance ?? 0);
            $newPending      = round($currentPending + $vendorAmount, 2);
            $currentApproved = (float) ($vendor->approved_payable_balance ?? 0);

            // Create the ledger row.
            $tx = VendorEarningTransaction::create([
                'vendor_id'              => $vendor->id,
                'order_id'               => $lockedOrder->id,
                'vendor_payout_id'       => null,
                'type'                   => VendorEarningTransaction::TYPE_PENDING_ORDER_EARNING,
                'status'                 => VendorEarningTransaction::STATUS_POSTED,
                'amount_delta'           => $vendorAmount,
                'pending_balance_after'  => $newPending,
                'approved_balance_after' => $currentApproved,
                'files_count'            => $filesCount,
                'rate_per_file'          => $ratePerFile,
                'created_by'             => $createdBy?->id,
                'notes'                  => 'Pending earning for order #' . $lockedOrder->id . ' on report upload.',
            ]);

            // Increase pending earning balance only — approved payable is untouched.
            $vendor->update(['pending_earning_balance' => $newPending]);

            return $tx;
        });
    }

    /**
     * Approve a pending vendor earning after admin accepts the delivered report.
     *
     * Moves the earning amount from pending_earning_balance → approved_payable_balance
     * and locks the order's financial snapshot (vendor_approved_at, gross_profit,
     * financial_locked_at).
     *
     * Rules:
     *  - Finds the posted pending_order_earning tx for this order.
     *  - Returns null if no pending earning exists (nothing to approve).
     *  - Idempotent: returns null if an approve_earning tx already exists.
     *  - Decreases vendor.pending_earning_balance by amount_delta.
     *  - Increases vendor.approved_payable_balance by amount_delta.
     *  - Creates an approve_earning tx row.
     *  - Sets orders.vendor_approved_at, orders.gross_profit, orders.financial_locked_at.
     *
     * @param  Order      $order       The delivered order to approve earning for
     * @param  User|null  $approvedBy  Admin actor
     * @param  string|null $notes      Optional admin note
     * @return VendorEarningTransaction|null  Null if skipped (no pending tx or already approved)
     */
    public function approveEarning(Order $order, ?User $approvedBy = null, ?string $notes = null): ?VendorEarningTransaction
    {
        return DB::transaction(function () use ($order, $approvedBy, $notes): ?VendorEarningTransaction {
            $lockedOrder = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            // Find the pending earning tx for this order.
            $pendingTx = VendorEarningTransaction::where('order_id', $lockedOrder->id)
                ->where('type', VendorEarningTransaction::TYPE_PENDING_ORDER_EARNING)
                ->where('status', VendorEarningTransaction::STATUS_POSTED)
                ->first();

            if (! $pendingTx) {
                Log::info('VendorEarningService: no pending earning tx for order #' . $lockedOrder->id . ' — approve skipped.');
                return null;
            }

            // Idempotency: already approved.
            $alreadyApproved = VendorEarningTransaction::where('order_id', $lockedOrder->id)
                ->where('type', VendorEarningTransaction::TYPE_APPROVE_EARNING)
                ->where('status', VendorEarningTransaction::STATUS_POSTED)
                ->exists();

            if ($alreadyApproved) {
                return null;
            }

            // Lock vendor row to prevent concurrent balance updates.
            $vendor = User::whereKey($pendingTx->vendor_id)->lockForUpdate()->firstOrFail();

            $amount          = (float) $pendingTx->amount_delta;
            $currentPending  = (float) ($vendor->pending_earning_balance ?? 0);
            $currentApproved = (float) ($vendor->approved_payable_balance ?? 0);

            $newPending  = round($currentPending - $amount, 2);
            $newApproved = round($currentApproved + $amount, 2);

            // Create the approve_earning ledger row.
            $tx = VendorEarningTransaction::create([
                'vendor_id'              => $vendor->id,
                'order_id'               => $lockedOrder->id,
                'vendor_payout_id'       => null,
                'type'                   => VendorEarningTransaction::TYPE_APPROVE_EARNING,
                'status'                 => VendorEarningTransaction::STATUS_POSTED,
                'amount_delta'           => $amount,
                'pending_balance_after'  => $newPending,
                'approved_balance_after' => $newApproved,
                'files_count'            => $pendingTx->files_count,
                'rate_per_file'          => $pendingTx->rate_per_file,
                'created_by'             => $approvedBy?->id,
                'notes'                  => $notes ?? 'Admin approved vendor earning for order #' . $lockedOrder->id . '.',
            ]);

            // Move balance: pending decreases, approved increases.
            $vendor->update([
                'pending_earning_balance'  => $newPending,
                'approved_payable_balance' => $newApproved,
            ]);

            // Lock in the order's financial snapshot.
            $clientAmount = (float) ($lockedOrder->client_amount ?? 0);
            $grossProfit  = round($clientAmount - $amount, 2);

            $lockedOrder->update([
                'vendor_approved_at'  => now(),
                'gross_profit'        => $grossProfit,
                'financial_locked_at' => now(),
            ]);

            return $tx;
        });
    }

    /**
     * Reverse a pending vendor earning after admin rejects the delivered report.
     *
     * Removes the earning from pending_earning_balance only.
     * approved_payable_balance is never touched.
     *
     * Rules:
     *  - Finds the posted pending_order_earning tx for this order.
     *  - Returns null if no pending earning exists (nothing to reverse).
     *  - Throws LogicException if the earning was already approved (Phase 6 scope).
     *  - Idempotent: returns null if a reversal tx already exists for this order.
     *  - Decreases vendor.pending_earning_balance by amount_delta.
     *  - Does NOT touch vendor.approved_payable_balance.
     *  - Creates a reversal tx row with a negative amount_delta.
     *  - Sets orders.vendor_rejected_at.
     *
     * @param  Order      $order       The delivered order to reject earning for
     * @param  User|null  $reversedBy  Admin actor
     * @param  string|null $reason     Optional admin note / rejection reason
     * @return VendorEarningTransaction|null  Null if skipped (no pending tx or already reversed)
     *
     * @throws \LogicException  If the earning was already approved (post-approval reversal not supported in Phase 6)
     */
    public function reverseEarning(Order $order, ?User $reversedBy = null, ?string $reason = null): ?VendorEarningTransaction
    {
        return DB::transaction(function () use ($order, $reversedBy, $reason): ?VendorEarningTransaction {
            $lockedOrder = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            // Find the pending earning tx for this order.
            $pendingTx = VendorEarningTransaction::where('order_id', $lockedOrder->id)
                ->where('type', VendorEarningTransaction::TYPE_PENDING_ORDER_EARNING)
                ->where('status', VendorEarningTransaction::STATUS_POSTED)
                ->first();

            if (! $pendingTx) {
                Log::info('VendorEarningService: no pending earning tx for order #' . $lockedOrder->id . ' — reversal skipped.');
                return null;
            }

            // Block post-approval reversal (Phase 6 scope).
            $isApproved = VendorEarningTransaction::where('order_id', $lockedOrder->id)
                ->where('type', VendorEarningTransaction::TYPE_APPROVE_EARNING)
                ->where('status', VendorEarningTransaction::STATUS_POSTED)
                ->exists();

            if ($isApproved) {
                throw new \LogicException(
                    'Cannot reject an already-approved vendor earning for order #' . $lockedOrder->id . '. ' .
                    'Post-approval reversal is not supported in Phase 6. Contact a senior admin.'
                );
            }

            // Idempotency: already reversed.
            $alreadyReversed = VendorEarningTransaction::where('order_id', $lockedOrder->id)
                ->where('type', VendorEarningTransaction::TYPE_REVERSAL)
                ->where('status', VendorEarningTransaction::STATUS_POSTED)
                ->exists();

            if ($alreadyReversed) {
                return null;
            }

            // Lock vendor row to prevent concurrent balance updates.
            $vendor = User::whereKey($pendingTx->vendor_id)->lockForUpdate()->firstOrFail();

            $amount          = (float) $pendingTx->amount_delta;
            $currentPending  = (float) ($vendor->pending_earning_balance ?? 0);
            $currentApproved = (float) ($vendor->approved_payable_balance ?? 0);

            $newPending = round($currentPending - $amount, 2);
            // approved_payable_balance is intentionally untouched.

            // Create the reversal ledger row (negative amount_delta).
            $tx = VendorEarningTransaction::create([
                'vendor_id'              => $vendor->id,
                'order_id'               => $lockedOrder->id,
                'vendor_payout_id'       => null,
                'type'                   => VendorEarningTransaction::TYPE_REVERSAL,
                'status'                 => VendorEarningTransaction::STATUS_POSTED,
                'amount_delta'           => -$amount,
                'pending_balance_after'  => $newPending,
                'approved_balance_after' => $currentApproved,
                'files_count'            => $pendingTx->files_count,
                'rate_per_file'          => $pendingTx->rate_per_file,
                'created_by'             => $reversedBy?->id,
                'notes'                  => $reason ?? 'Admin rejected vendor earning for order #' . $lockedOrder->id . '.',
            ]);

            // Decrease pending balance only — approved balance is frozen.
            $vendor->update(['pending_earning_balance' => $newPending]);

            // Stamp the rejection on the order.
            $lockedOrder->update(['vendor_rejected_at' => now()]);

            return $tx;
        });
    }
}
