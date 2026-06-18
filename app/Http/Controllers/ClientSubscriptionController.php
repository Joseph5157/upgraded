<?php

namespace App\Http\Controllers;

use App\Models\ClientPayment;
use App\Models\Order;
use App\Enums\OrderStatus;
use Illuminate\Support\Facades\Auth;

class ClientSubscriptionController extends Controller
{
    public function index()
    {
        $user   = Auth::user();
        $client = $user->client;

        if (!$client) {
            abort(403, 'No client account linked.');
        }

        $creditsRemaining = max(0, (int) $client->credit_balance);

        // Credits used = total debits from the credit ledger
        $creditsUsed = (int) abs(
            $client->creditTransactions()
                ->where('type', 'order_debit')
                ->sum('credits_delta')
        );

        $paymentHistory = $client->clientPayments()
            ->with('createdBy')
            ->latest('received_at')
            ->get();

        $refundHistory = $client->refundRequests()
            ->with('order')
            ->latest()
            ->get();

        $lastPayment = $paymentHistory->where('status', 'confirmed')->first();

        return view('client.subscription', compact(
            'client',
            'creditsUsed',
            'creditsRemaining',
            'paymentHistory',
            'refundHistory',
            'lastPayment'
        ));
    }
}
