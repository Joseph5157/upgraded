<?php

namespace App\Services\Finance;

use App\Models\User;
use App\Models\VendorEarningTransaction;
use App\Models\VendorPayout;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * VendorPayoutService
 *
 * Records a payment from admin to vendor against the vendor's
 * approved_payable_balance only. Pending earnings cannot be paid out
 * until admin approves them first (Phase 6).
 *
 * Every recordPayout() call results in:
 *  1. A vendor_payouts row (the external payment record)
 *  2. A vendor_earning_transactions row with type = payout (the ledger entry)
 *  3. users.approved_payable_balance decremented by the payout amount
 *
 * users.pending_earning_balance is never touched here.
 */
class VendorPayoutService
{
    /**
     * Record a vendor payout and update the vendor's approved payable balance.
     *
     * @param  User       $vendor  The vendor receiving payment
     * @param  array      $data    {
     *   amount         float   required, > 0, <= approved_payable_balance
     *   payment_mode   string  required: upi | bank_transfer | cash
     *   transaction_id string|null  reference / UTR; required for upi/bank_transfer
     *   paid_at        string|null  defaults to now()
     *   notes          string|null
     * }
     * @param  User|null  $paidBy  Admin user recording the payout
     * @return VendorPayout
     *
     * @throws \InvalidArgumentException  For validation failures (role, amount, duplicate tx)
     * @throws \RuntimeException          For balance constraint failures
     */
    public function recordPayout(User $vendor, array $data, ?User $paidBy = null): VendorPayout
    {
        return DB::transaction(function () use ($vendor, $data, $paidBy): VendorPayout {
            // Lock the vendor row for the duration of this transaction.
            $locked = User::whereKey($vendor->id)->lockForUpdate()->firstOrFail();

            if ($locked->role !== 'vendor') {
                throw new \InvalidArgumentException(
                    "User #{$locked->id} ({$locked->name}) is not a vendor and cannot receive a vendor payout."
                );
            }

            $amount = (float) ($data['amount'] ?? 0);

            if ($amount <= 0) {
                throw new \InvalidArgumentException('Payout amount must be greater than zero.');
            }

            $approvedBalance = (float) ($locked->approved_payable_balance ?? 0);

            if ($amount > $approvedBalance) {
                throw new \RuntimeException(
                    'Payout of ₹' . number_format($amount, 2) .
                    " exceeds {$locked->name}'s approved payable balance of ₹" .
                    number_format($approvedBalance, 2) . '. ' .
                    'Only approved earnings can be paid out. Approve pending earnings first.'
                );
            }

            $paymentMode   = $data['payment_mode'] ?? null;
            $transactionId = $data['transaction_id'] ?? null;
            $paidAt        = isset($data['paid_at']) && $data['paid_at']
                ? \Carbon\Carbon::parse($data['paid_at'])
                : now();

            // Prevent duplicate transaction ID per payment mode (not applicable for cash).
            if ($transactionId && $paymentMode && $paymentMode !== 'cash') {
                $duplicate = VendorPayout::where('payment_mode', $paymentMode)
                    ->where('reference_id', $transactionId)
                    ->exists();

                if ($duplicate) {
                    throw new \InvalidArgumentException(
                        "A payout with transaction ID '{$transactionId}' for mode '{$paymentMode}' already exists."
                    );
                }
            }

            // Create the vendor_payouts row.
            $payout = VendorPayout::create([
                'user_id'      => $locked->id,
                'amount'       => $amount,
                'reference_id' => $transactionId,
                'payment_mode' => $paymentMode,
                'paid_by'      => $paidBy?->id,
                'status'       => 'paid',
                'paid_at'      => $paidAt,
                'notes'        => $data['notes'] ?? null,
            ]);

            // Compute new approved payable balance.
            $newApproved    = round($approvedBalance - $amount, 2);
            $currentPending = (float) ($locked->pending_earning_balance ?? 0);

            // Create the ledger entry (negative amount_delta = outflow from approved pool).
            VendorEarningTransaction::create([
                'vendor_id'              => $locked->id,
                'order_id'               => null,
                'vendor_payout_id'       => $payout->id,
                'type'                   => VendorEarningTransaction::TYPE_PAYOUT,
                'status'                 => VendorEarningTransaction::STATUS_POSTED,
                'amount_delta'           => -$amount,
                'pending_balance_after'  => $currentPending,
                'approved_balance_after' => $newApproved,
                'files_count'            => 0,
                'rate_per_file'          => null,
                'created_by'             => $paidBy?->id,
                'notes'                  => $data['notes']
                    ?? 'Payout of ₹' . number_format($amount, 2) . ' via ' . ($paymentMode ?? 'unknown') . '.',
            ]);

            // Decrement approved payable balance only — pending balance is frozen.
            $locked->update(['approved_payable_balance' => $newApproved]);

            Log::info('vendor.payout_recorded', [
                'vendor_id'    => $locked->id,
                'payout_id'    => $payout->id,
                'amount'       => $amount,
                'payment_mode' => $paymentMode,
                'new_approved' => $newApproved,
                'paid_by'      => $paidBy?->id,
            ]);

            return $payout;
        });
    }
}
