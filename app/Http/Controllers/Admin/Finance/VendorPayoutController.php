<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreVendorPayoutRequest;
use App\Models\User;
use App\Models\VendorPayout;
use App\Services\Finance\FinanceVoidService;
use App\Services\Finance\VendorPayoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class VendorPayoutController extends Controller
{
    public function __construct(
        protected VendorPayoutService $payoutService,
        protected FinanceVoidService $voidService,
    ) {}

    /**
     * Vendor payables summary + payout history.
     */
    public function index()
    {
        $this->authorize('viewAny', VendorPayout::class);

        $vendors = User::where('role', 'vendor')
            ->orderBy('name')
            ->get()
            ->map(function (User $vendor) {
                return [
                    'vendor'           => $vendor,
                    'pending_earning'  => (float) ($vendor->pending_earning_balance ?? 0),
                    'approved_payable' => (float) ($vendor->approved_payable_balance ?? 0),
                    'total_paid'       => (float) VendorPayout::where('user_id', $vendor->id)
                                             ->where('status', 'paid')
                                             ->sum('amount'),
                    'last_payout_at'   => VendorPayout::where('user_id', $vendor->id)
                                             ->where('status', 'paid')
                                             ->latest('paid_at')
                                             ->value('paid_at'),
                ];
            });

        $payoutHistory = VendorPayout::with(['vendor', 'paidBy'])
            ->where('status', 'paid')
            ->latest('paid_at')
            ->get();

        return view('admin.finance.payouts', compact('vendors', 'payoutHistory'));
    }

    /**
     * Record a payout against the vendor's approved payable balance.
     */
    public function store(StoreVendorPayoutRequest $request)
    {
        $vendor = User::findOrFail($request->validated()['vendor_id']);

        try {
            $payout = $this->payoutService->recordPayout(
                $vendor,
                [
                    'amount'         => $request->validated()['amount'],
                    'payment_mode'   => $request->validated()['payment_mode'],
                    'transaction_id' => $request->validated()['transaction_id'] ?? null,
                    'paid_at'        => $request->validated()['paid_at'] ?? null,
                    'notes'          => $request->validated()['notes'] ?? null,
                ],
                Auth::user(),
            );
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        Cache::forget('admin_nav_pending_vendor_earnings');

        return back()->with(
            'success',
            'Payout of ₹' . number_format($payout->amount, 0) .
            ' to ' . $vendor->name . ' recorded successfully.'
        );
    }

    /**
     * Show detail for a single payout.
     */
    public function show(VendorPayout $vendorPayout)
    {
        $this->authorize('viewAny', VendorPayout::class);

        $vendorPayout->load(['vendor', 'paidBy', 'earningTransactions']);

        return view('admin.finance.payouts-show', compact('vendorPayout'));
    }

    public function void(Request $request, VendorPayout $vendorPayout): RedirectResponse
    {
        $this->authorize('viewAny', VendorPayout::class);

        $request->validate(['void_reason' => 'required|string|max:2000']);

        $voided = $this->voidService->voidVendorPayout(
            $vendorPayout,
            $request->user(),
            $request->input('void_reason'),
        );

        if (! $voided) {
            return back()->with('info', 'Payout was already voided.');
        }

        return back()->with('success', "Payout #{$vendorPayout->id} has been voided. Vendor balance restored.");
    }
}
