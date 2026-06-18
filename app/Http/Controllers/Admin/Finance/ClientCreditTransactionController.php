<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ClientCreditTransaction;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClientCreditTransactionController extends Controller
{
    public function index(Request $request): View
    {
        $query = ClientCreditTransaction::with(['client', 'clientPayment', 'order', 'createdBy'])
            ->orderByDesc('id');

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->integer('client_id'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->string('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->string('date_to'));
        }

        $transactions = $query->paginate(30)->withQueryString();

        $clients = Client::where('status', '!=', 'deleted')
            ->orderBy('name')
            ->get();

        $types = [
            ClientCreditTransaction::TYPE_OPENING_BALANCE   => 'Opening Balance',
            ClientCreditTransaction::TYPE_PAYMENT_CREDIT    => 'Payment Credit',
            ClientCreditTransaction::TYPE_ORDER_DEBIT       => 'Order Debit',
            ClientCreditTransaction::TYPE_REFUND_CREDIT     => 'Refund Credit',
            ClientCreditTransaction::TYPE_MANUAL_ADJUSTMENT => 'Manual Adjustment',
            ClientCreditTransaction::TYPE_CORRECTION        => 'Correction',
        ];

        return view('admin.finance.client-credit-transactions.index', compact(
            'transactions', 'clients', 'types'
        ));
    }
}
