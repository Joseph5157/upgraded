<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ClientCreditTransaction;
use App\Models\ClientPayment;
use Illuminate\View\View;

class ClientBalanceController extends Controller
{
    public function index(): View
    {
        $clients = Client::with('user')
            ->where('status', '!=', 'deleted')
            ->orderBy('name')
            ->get()
            ->map(function (Client $client) {
                $payments = ClientPayment::where('client_id', $client->id)
                    ->where('status', ClientPayment::STATUS_CONFIRMED);

                $debited = ClientCreditTransaction::where('client_id', $client->id)
                    ->where('type', ClientCreditTransaction::TYPE_ORDER_DEBIT)
                    ->sum('credits_delta'); // negative integers

                $refunded = ClientCreditTransaction::where('client_id', $client->id)
                    ->where('type', ClientCreditTransaction::TYPE_REFUND_CREDIT)
                    ->sum('credits_delta'); // positive integers

                return [
                    'client'           => $client,
                    'credit_balance'   => $client->credit_balance,
                    'total_received'   => (float) (clone $payments)->sum('amount_received'),
                    'credits_added'    => (int)   (clone $payments)->sum('credits_added'),
                    'credits_used'     => abs((int) $debited),
                    'credits_refunded' => (int) $refunded,
                    'last_payment_at'  => ClientPayment::where('client_id', $client->id)
                        ->latest('received_at')
                        ->value('received_at'),
                ];
            });

        return view('admin.finance.client-balances.index', compact('clients'));
    }
}
