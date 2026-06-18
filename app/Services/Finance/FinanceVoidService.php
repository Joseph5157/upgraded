<?php

namespace App\Services\Finance;

use App\Models\BusinessExpense;
use App\Models\Client;
use App\Models\ClientCreditTransaction;
use App\Models\ClientPayment;
use App\Models\User;
use App\Models\VendorEarningTransaction;
use App\Models\VendorPayout;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * FinanceVoidService — Phase 10B
 *
 * Safe voiding of finance records. Never hard-deletes or directly edits
 * original amounts. Creates reversal ledger entries to maintain audit trail.
 *
 * All methods are idempotent: voiding an already-voided record is a no-op.
 */
class FinanceVoidService
{
    /**
     * Void a client payment and reverse the credits it added.
     *
     * Rules:
     *  - If the client's credit_balance is less than the credits_added by this
     *    payment, the void is blocked (credits have been used).
     *  - A correction credit transaction is created with negative delta.
     *  - The payment is marked voided with metadata.
     *  - Already-voided payments are silently skipped (idempotent).
     *
     * @throws \RuntimeException If credits have been used and balance is insufficient.
     */
    public function voidClientPayment(ClientPayment $payment, User $voidedBy, string $reason): bool
    {
        if ($payment->status === ClientPayment::STATUS_VOIDED) {
            return false; // Already voided — idempotent
        }

        return DB::transaction(function () use ($payment, $voidedBy, $reason): bool {
            // Re-read inside transaction to prevent race
            $payment = ClientPayment::where('id', $payment->id)->lockForUpdate()->firstOrFail();

            if ($payment->status === ClientPayment::STATUS_VOIDED) {
                return false;
            }

            $creditsToReverse = (int) $payment->credits_added;
            $client = Client::where('id', $payment->client_id)->lockForUpdate()->firstOrFail();

            if ($creditsToReverse > 0 && (int) $client->credit_balance < $creditsToReverse) {
                throw new \RuntimeException(
                    'Cannot void this payment automatically because credits have already been used. ' .
                    "Client balance is {$client->credit_balance} but {$creditsToReverse} credits need to be reversed. " .
                    'Use manual correction flow.'
                );
            }

            // Create reversal credit transaction
            if ($creditsToReverse > 0) {
                $newBalance = (int) $client->credit_balance - $creditsToReverse;

                ClientCreditTransaction::create([
                    'client_id'         => $client->id,
                    'order_id'          => null,
                    'client_payment_id' => $payment->id,
                    'type'              => ClientCreditTransaction::TYPE_CORRECTION,
                    'credits_delta'     => -$creditsToReverse,
                    'balance_after'     => $newBalance,
                    'rate_per_credit'   => $payment->rate_per_credit,
                    'money_value'       => -abs((float) $payment->amount_received),
                    'created_by'        => $voidedBy->id,
                    'notes'             => "Void reversal for payment #{$payment->id}. Reason: {$reason}",
                ]);

                $client->update(['credit_balance' => $newBalance]);
            }

            // Mark payment voided
            $payment->update([
                'status'      => ClientPayment::STATUS_VOIDED,
                'voided_at'   => now(),
                'voided_by'   => $voidedBy->id,
                'void_reason' => $reason,
            ]);

            Log::info('finance.client_payment_voided', [
                'payment_id'       => $payment->id,
                'client_id'        => $client->id,
                'credits_reversed' => $creditsToReverse,
                'amount'           => $payment->amount_received,
                'voided_by'        => $voidedBy->id,
                'reason'           => $reason,
            ]);

            return true;
        });
    }

    /**
     * Void a vendor payout and restore the approved payable balance.
     *
     * Rules:
     *  - Restores the payout amount to vendor's approved_payable_balance.
     *  - Creates a payout_reversal earning transaction.
     *  - Does NOT touch pending_earning_balance.
     *  - Already-voided payouts are silently skipped.
     */
    public function voidVendorPayout(VendorPayout $payout, User $voidedBy, string $reason): bool
    {
        if ($payout->status === 'voided') {
            return false;
        }

        return DB::transaction(function () use ($payout, $voidedBy, $reason): bool {
            $payout = VendorPayout::where('id', $payout->id)->lockForUpdate()->firstOrFail();

            if ($payout->status === 'voided') {
                return false;
            }

            $vendor = User::where('id', $payout->user_id)->lockForUpdate()->firstOrFail();
            $amount = (float) $payout->amount;

            $newApproved    = round((float) $vendor->approved_payable_balance + $amount, 2);
            $currentPending = (float) $vendor->pending_earning_balance;

            // Create reversal earning transaction
            VendorEarningTransaction::create([
                'vendor_id'              => $vendor->id,
                'order_id'               => null,
                'vendor_payout_id'       => $payout->id,
                'type'                   => VendorEarningTransaction::TYPE_PAYOUT_REVERSAL,
                'status'                 => VendorEarningTransaction::STATUS_POSTED,
                'amount_delta'           => $amount,
                'pending_balance_after'  => $currentPending,
                'approved_balance_after' => $newApproved,
                'files_count'            => 0,
                'rate_per_file'          => null,
                'created_by'             => $voidedBy->id,
                'notes'                  => "Void reversal for payout #{$payout->id}. Reason: {$reason}",
            ]);

            // Restore vendor balance
            $vendor->update(['approved_payable_balance' => $newApproved]);

            // Mark payout voided
            $payout->update([
                'status'      => 'voided',
                'voided_at'   => now(),
                'voided_by'   => $voidedBy->id,
                'void_reason' => $reason,
            ]);

            Log::info('finance.vendor_payout_voided', [
                'payout_id'    => $payout->id,
                'vendor_id'    => $vendor->id,
                'amount'       => $amount,
                'new_approved' => $newApproved,
                'voided_by'    => $voidedBy->id,
                'reason'       => $reason,
            ]);

            return true;
        });
    }

    /**
     * Void a business expense.
     *
     * Rules:
     *  - Marks the expense as voided with metadata.
     *  - No balance mutations needed (expenses don't affect ledger balances).
     *  - Already-voided expenses are silently skipped.
     */
    public function voidBusinessExpense(BusinessExpense $expense, User $voidedBy, string $reason): bool
    {
        if ($expense->status === BusinessExpense::STATUS_VOIDED) {
            return false;
        }

        $expense->update([
            'status'      => BusinessExpense::STATUS_VOIDED,
            'voided_at'   => now(),
            'voided_by'   => $voidedBy->id,
            'void_reason' => $reason,
        ]);

        Log::info('finance.business_expense_voided', [
            'expense_id' => $expense->id,
            'amount'     => $expense->amount,
            'category'   => $expense->category,
            'voided_by'  => $voidedBy->id,
            'reason'     => $reason,
        ]);

        return true;
    }
}
