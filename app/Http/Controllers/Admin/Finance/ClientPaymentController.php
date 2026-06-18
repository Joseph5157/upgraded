<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreClientPaymentRequest;
use App\Models\Client;
use App\Models\ClientPayment;
use App\Services\Finance\ClientPaymentService;
use App\Services\Finance\FinanceVoidService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClientPaymentController extends Controller
{
    public function __construct(
        protected ClientPaymentService $paymentService,
        protected FinanceVoidService $voidService,
    ) {}

    public function index(): View
    {
        $payments = ClientPayment::with(['client', 'createdBy'])
            ->orderByDesc('received_at')
            ->orderByDesc('id')
            ->paginate(25);

        $clients = Client::where('status', '!=', 'deleted')
            ->orderBy('name')
            ->get();

        $totals = [
            'amount'  => ClientPayment::where('status', ClientPayment::STATUS_CONFIRMED)->sum('amount_received'),
            'credits' => ClientPayment::where('status', ClientPayment::STATUS_CONFIRMED)->sum('credits_added'),
        ];

        return view('admin.finance.client-payments.index', compact('payments', 'clients', 'totals'));
    }

    public function show(ClientPayment $clientPayment): View
    {
        $clientPayment->load(['client.user', 'createdBy', 'creditTransactions.order']);

        return view('admin.finance.client-payments.show', compact('clientPayment'));
    }

    public function store(StoreClientPaymentRequest $request): RedirectResponse
    {
        $client = Client::findOrFail($request->validated('client_id'));

        $this->paymentService->record($client, $request->validated(), $request->user());

        $credits = $request->validated('credits_added');

        return redirect()
            ->route('admin.finance.client-payments.index')
            ->with('success', "{$credits} credits added to {$client->name}. Payment recorded.");
    }

    public function void(Request $request, ClientPayment $clientPayment): RedirectResponse
    {
        $request->validate(['void_reason' => 'required|string|max:2000']);

        try {
            $voided = $this->voidService->voidClientPayment(
                $clientPayment,
                $request->user(),
                $request->input('void_reason'),
            );
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        if (! $voided) {
            return back()->with('info', 'Payment was already voided.');
        }

        return back()->with('success', "Payment #{$clientPayment->id} has been voided. Credits reversed.");
    }
}
