<?php

namespace App\Services\Finance;

use App\Models\Client;
use App\Models\ClientPayment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ClientPaymentService
{
    public function __construct(
        protected ClientCreditService $creditService,
    ) {}

    /**
     * Record a client payment and credit the corresponding credits.
     *
     * Owns the DB transaction: creates the payment row, locks the client
     * row, then calls ClientCreditService to add credits and write the ledger.
     *
     * @param  Client  $client
     * @param  array   $data    Keys: amount_received, credits_added, payment_mode,
     *                           transaction_id (nullable), received_at, notes (nullable)
     * @param  User    $admin   The admin recording the payment (stored as created_by)
     * @return ClientPayment
     */
    public function record(Client $client, array $data, User $admin): ClientPayment
    {
        $credits       = (int) $data['credits_added'];
        $amountReceived = (string) $data['amount_received'];
        $ratePerCredit  = $credits > 0
            ? round((float) $amountReceived / $credits, 2)
            : 0.00;

        return DB::transaction(function () use ($client, $data, $admin, $credits, $amountReceived, $ratePerCredit) {
            $payment = ClientPayment::create([
                'client_id'       => $client->id,
                'amount_received' => $amountReceived,
                'credits_added'   => $credits,
                'rate_per_credit' => $ratePerCredit,
                'payment_mode'    => $data['payment_mode'],
                'transaction_id'  => $data['transaction_id'] ?? null,
                'received_at'     => $data['received_at'],
                'created_by'      => $admin->id,
                'notes'           => $data['notes'] ?? null,
                'status'          => ClientPayment::STATUS_CONFIRMED,
            ]);

            $locked = Client::where('id', $client->id)->lockForUpdate()->first();

            $this->creditService->creditClient($locked, $credits, [
                'client_payment_id' => $payment->id,
                'rate_per_credit'   => $ratePerCredit,
                'money_value'       => $amountReceived,
                'created_by'        => $admin->id,
            ]);

            return $payment;
        });
    }

    /**
     * Approve a pending client payment: transition to confirmed and credit the client.
     *
     * Uses ClientCreditService internally — same ledger path as record().
     *
     * @throws \InvalidArgumentException  If payment is not in pending status
     */
    public function approve(ClientPayment $payment, User $approvedBy): ClientPayment
    {
        if ($payment->status !== ClientPayment::STATUS_PENDING) {
            throw new \InvalidArgumentException(
                "Payment #{$payment->id} cannot be approved — current status is '{$payment->status}'."
            );
        }

        return DB::transaction(function () use ($payment, $approvedBy): ClientPayment {
            $locked = ClientPayment::whereKey($payment->id)->lockForUpdate()->firstOrFail();

            // Re-check after locking (concurrent approval guard)
            if ($locked->status !== ClientPayment::STATUS_PENDING) {
                throw new \InvalidArgumentException(
                    "Payment #{$locked->id} was already processed (status: {$locked->status})."
                );
            }

            $locked->update([
                'status'      => ClientPayment::STATUS_CONFIRMED,
                'received_at' => $locked->received_at ?? now(),
            ]);

            $client  = Client::where('id', $locked->client_id)->lockForUpdate()->first();
            $credits = (int) $locked->credits_added;

            if ($credits > 0) {
                $this->creditService->creditClient($client, $credits, [
                    'client_payment_id' => $locked->id,
                    'rate_per_credit'   => $locked->rate_per_credit,
                    'money_value'       => $locked->amount_received,
                    'created_by'        => $approvedBy->id,
                ]);
            }

            return $locked->fresh();
        });
    }

    /**
     * Reject a pending client payment.
     *
     * No credits are added. The payment is marked as rejected with the reason.
     *
     * @throws \InvalidArgumentException  If payment is not in pending status
     */
    public function reject(ClientPayment $payment, User $rejectedBy, string $reason = ''): ClientPayment
    {
        if ($payment->status !== ClientPayment::STATUS_PENDING) {
            throw new \InvalidArgumentException(
                "Payment #{$payment->id} cannot be rejected — current status is '{$payment->status}'."
            );
        }

        return DB::transaction(function () use ($payment, $rejectedBy, $reason): ClientPayment {
            $locked = ClientPayment::whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== ClientPayment::STATUS_PENDING) {
                throw new \InvalidArgumentException(
                    "Payment #{$locked->id} was already processed (status: {$locked->status})."
                );
            }

            $locked->update([
                'status'      => ClientPayment::STATUS_REJECTED,
                'voided_by'   => $rejectedBy->id,
                'voided_at'   => now(),
                'void_reason' => $reason ?: 'Rejected via Telegram',
            ]);

            return $locked->fresh();
        });
    }
}
