<?php

namespace App\Services\Finance;

use App\Models\Client;
use App\Models\ClientCreditTransaction;
use App\Models\Order;

/**
 * ClientCreditService
 *
 * Every method that mutates clients.credit_balance MUST be called:
 *   (a) Inside an active DB::transaction(), AND
 *   (b) With a $client instance retrieved via lockForUpdate().
 *
 * The caller (service or command) owns the transaction boundary.
 * This allows multiple operations to be composed into one atomic unit
 * without nested transaction issues.
 *
 * Example caller pattern:
 *
 *   DB::transaction(function () use ($client) {
 *       $locked = Client::where('id', $client->id)->lockForUpdate()->first();
 *       $this->creditService->creditClient($locked, 10, [...]);
 *   });
 */
class ClientCreditService
{
    /**
     * Create an opening-balance credit transaction for the slot migration.
     *
     * Idempotent: skips silently if credits_migrated_at is already set.
     * The caller must check the locked client before calling to avoid
     * a race condition window.
     *
     * @param  Client  $client  Locked client inside an active transaction
     * @param  int     $slots   Remaining slot balance to convert to credits
     * @param  array   $meta    Optional: created_by, notes
     * @return ClientCreditTransaction|null  Null if already migrated
     */
    public function createOpeningBalance(Client $client, int $slots, array $meta = []): ?ClientCreditTransaction
    {
        if ($client->credits_migrated_at !== null) {
            return null;
        }

        $slots = max(0, $slots);

        $tx = ClientCreditTransaction::create([
            'client_id'         => $client->id,
            'order_id'          => null,
            'client_payment_id' => null,
            'type'              => ClientCreditTransaction::TYPE_OPENING_BALANCE,
            'credits_delta'     => $slots,
            'balance_after'     => $slots,
            'rate_per_credit'   => null,
            'money_value'       => null,
            'created_by'        => $meta['created_by'] ?? null,
            'notes'             => $meta['notes'] ?? 'Migrated from old slots system.',
        ]);

        $client->update([
            'credit_balance'      => $slots,
            'credits_migrated_at' => now(),
        ]);

        return $tx;
    }

    /**
     * Add credits to a client (e.g. when admin records a payment).
     *
     * @param  Client  $client   Locked client inside an active transaction
     * @param  int     $credits  Number of credits to add — must be > 0
     * @param  array   $meta     Optional: client_payment_id, rate_per_credit,
     *                            money_value, created_by, notes
     * @return ClientCreditTransaction
     *
     * @throws \InvalidArgumentException if $credits <= 0
     */
    public function creditClient(Client $client, int $credits, array $meta = []): ClientCreditTransaction
    {
        if ($credits <= 0) {
            throw new \InvalidArgumentException(
                "Credits to add must be a positive integer, got {$credits}."
            );
        }

        $newBalance = (int) $client->credit_balance + $credits;

        $tx = ClientCreditTransaction::create([
            'client_id'         => $client->id,
            'order_id'          => null,
            'client_payment_id' => $meta['client_payment_id'] ?? null,
            'type'              => ClientCreditTransaction::TYPE_PAYMENT_CREDIT,
            'credits_delta'     => $credits,
            'balance_after'     => $newBalance,
            'rate_per_credit'   => $meta['rate_per_credit'] ?? null,
            'money_value'       => $meta['money_value'] ?? null,
            'created_by'        => $meta['created_by'] ?? null,
            'notes'             => $meta['notes'] ?? null,
        ]);

        $client->update(['credit_balance' => $newBalance]);

        return $tx;
    }

    /**
     * Debit credits when a client order is created.
     *
     * Uses order.credits_consumed as the debit amount.
     * Prevents: insufficient balance, and duplicate debits for the same order.
     *
     * @param  Client  $client  Locked client inside an active transaction
     * @param  Order   $order   The order being created (already persisted)
     * @param  array   $meta    Optional: created_by, notes
     * @return ClientCreditTransaction
     *
     * @throws \RuntimeException if insufficient credits or already debited
     */
    public function debitForOrder(Client $client, Order $order, array $meta = []): ClientCreditTransaction
    {
        $credits = (int) ($order->credits_consumed ?? 1);

        $currentBalance = (int) $client->credit_balance;

        if ($currentBalance < $credits) {
            throw new \RuntimeException(
                "Insufficient credits for order #{$order->id}. " .
                "Required: {$credits}, available: {$currentBalance}."
            );
        }

        $alreadyDebited = ClientCreditTransaction::where('order_id', $order->id)
            ->where('type', ClientCreditTransaction::TYPE_ORDER_DEBIT)
            ->exists();

        if ($alreadyDebited) {
            throw new \RuntimeException(
                "Credits have already been debited for order #{$order->id}."
            );
        }

        $newBalance = $currentBalance - $credits;

        $tx = ClientCreditTransaction::create([
            'client_id'         => $client->id,
            'order_id'          => $order->id,
            'client_payment_id' => null,
            'type'              => ClientCreditTransaction::TYPE_ORDER_DEBIT,
            'credits_delta'     => -$credits,
            'balance_after'     => $newBalance,
            'rate_per_credit'   => $meta['rate_per_credit'] ?? null,
            'money_value'       => $meta['money_value'] ?? null,
            'created_by'        => $meta['created_by'] ?? null,
            'notes'             => $meta['notes'] ?? null,
        ]);

        $client->update(['credit_balance' => $newBalance]);

        return $tx;
    }

    /**
     * Refund credits when an order is cancelled before delivery.
     *
     * Idempotent: returns null if order.credits_refunded_at is already set
     * or if a refund_credit transaction already exists for this order.
     *
     * @param  Client  $client  Locked client inside an active transaction
     * @param  Order   $order   Cancelled order (locked by caller)
     * @param  array   $meta    Optional: created_by, notes
     * @return ClientCreditTransaction|null  Null if already refunded
     */
    public function refundForOrder(Client $client, Order $order, array $meta = []): ?ClientCreditTransaction
    {
        // Primary idempotency guard: timestamp on the order
        if ($order->credits_refunded_at !== null) {
            return null;
        }

        // Secondary guard: check ledger for existing refund row
        $alreadyRefunded = ClientCreditTransaction::where('order_id', $order->id)
            ->where('type', ClientCreditTransaction::TYPE_REFUND_CREDIT)
            ->exists();

        if ($alreadyRefunded) {
            // Heal the timestamp if somehow missing
            if ($order->credits_refunded_at === null) {
                $order->update(['credits_refunded_at' => now()]);
            }
            return null;
        }

        $credits = (int) ($order->credits_consumed ?? 1);
        $newBalance = (int) $client->credit_balance + $credits;

        $tx = ClientCreditTransaction::create([
            'client_id'         => $client->id,
            'order_id'          => $order->id,
            'client_payment_id' => null,
            'type'              => ClientCreditTransaction::TYPE_REFUND_CREDIT,
            'credits_delta'     => $credits,
            'balance_after'     => $newBalance,
            'rate_per_credit'   => null,
            'money_value'       => null,
            'created_by'        => $meta['created_by'] ?? null,
            'notes'             => $meta['notes'] ?? 'Credit refund on order cancellation.',
        ]);

        $client->update(['credit_balance' => $newBalance]);
        $order->update(['credits_refunded_at' => now()]);

        return $tx;
    }

    /**
     * Apply a manual credit adjustment (positive or negative delta).
     *
     * Notes are mandatory for manual adjustments.
     * A negative delta will not be allowed if it would cause the balance
     * to go below zero.
     *
     * @param  Client  $client  Locked client inside an active transaction
     * @param  int     $delta   Credits to add (+) or remove (−); must not be 0
     * @param  string  $notes   Mandatory explanation for the adjustment
     * @param  array   $meta    Optional: created_by
     * @return ClientCreditTransaction
     *
     * @throws \InvalidArgumentException if delta is 0 or notes are empty
     * @throws \RuntimeException         if adjustment would go below zero
     */
    public function adjustCredits(Client $client, int $delta, string $notes, array $meta = []): ClientCreditTransaction
    {
        if ($delta === 0) {
            throw new \InvalidArgumentException('Adjustment delta cannot be zero.');
        }

        if (trim($notes) === '') {
            throw new \InvalidArgumentException('Notes are required for manual credit adjustments.');
        }

        $newBalance = (int) $client->credit_balance + $delta;

        if ($newBalance < 0) {
            throw new \RuntimeException(
                "Adjustment of {$delta} would result in a negative balance " .
                "({$newBalance}) for client #{$client->id}. Reduce the adjustment amount."
            );
        }

        $tx = ClientCreditTransaction::create([
            'client_id'         => $client->id,
            'order_id'          => null,
            'client_payment_id' => null,
            'type'              => ClientCreditTransaction::TYPE_MANUAL_ADJUSTMENT,
            'credits_delta'     => $delta,
            'balance_after'     => $newBalance,
            'rate_per_credit'   => null,
            'money_value'       => null,
            'created_by'        => $meta['created_by'] ?? null,
            'notes'             => $notes,
        ]);

        $client->update(['credit_balance' => $newBalance]);

        return $tx;
    }

    /**
     * Refund credits for an order only if a TYPE_ORDER_DEBIT transaction exists.
     *
     * This is the Phase 4B safe refund helper. It guards against phantom
     * credit refunds for pre-Phase-4 orders that never debited credit_balance.
     *
     * Rules:
     *  - Returns false immediately if no TYPE_ORDER_DEBIT exists for the order.
     *  - Returns false (idempotent) if credits were already refunded.
     *  - Otherwise delegates to refundForOrder() and returns true.
     *  - Never touches slots or slots_consumed.
     *
     * The caller must supply a locked client inside an active DB::transaction().
     *
     * @param  Client      $client     Locked client inside an active transaction
     * @param  Order       $order      The order to check and optionally refund
     * @param  \App\Models\User|null  $createdBy  User performing the action (for audit)
     * @param  string|null $reason     Optional note stored on the ledger row
     * @return bool  True if a new refund_credit was created; false if skipped
     */
    public function refundOrderIfDebited(Client $client, Order $order, ?\App\Models\User $createdBy = null, ?string $reason = null): bool
    {
        $hasDebitTx = ClientCreditTransaction::where('order_id', $order->id)
            ->where('type', ClientCreditTransaction::TYPE_ORDER_DEBIT)
            ->exists();

        if (! $hasDebitTx) {
            return false;
        }

        $tx = $this->refundForOrder($client, $order, [
            'created_by' => $createdBy?->id,
            'notes'      => $reason ?? 'Credit refund on order cancellation/refund.',
        ]);

        return $tx !== null;
    }

    /**
     * Return the cached credit balance for a client.
     *
     * For display only. Do not rely on this value inside a write transaction
     * without first re-reading the row with lockForUpdate().
     */
    public function getBalance(Client $client): int
    {
        return (int) $client->credit_balance;
    }
}
