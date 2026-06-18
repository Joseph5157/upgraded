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
}
