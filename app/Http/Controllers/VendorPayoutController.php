<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\VendorPayout;
use App\Models\VendorPayoutRequest;
use App\Services\PortalTelegramAlertService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VendorPayoutController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', VendorPayout::class);

        $payoutRate = config('services.portal.vendor_payout_per_order');

        // All vendors
        $vendors = User::where('role', 'vendor')->get();

        // Compute balance for each vendor
        $vendorData = $vendors->map(function ($vendor) use ($payoutRate) {
            // Use the permanent delivered_orders_count on the vendor's profile.
            // DO NOT count from orders table — client may have deleted delivered orders
            // which would reduce the count and wipe vendor's earned credits.
            $delivered = $vendor->delivered_orders_count;

            $rate    = $vendor->payout_rate ?? $payoutRate;
            $earned  = $delivered * $rate;
            $paid    = VendorPayout::where('user_id', $vendor->id)->sum('amount');
            $balance = $earned - $paid;

            return [
                'vendor'    => $vendor,
                'delivered' => $delivered,
                'rate'      => $rate,
                'earned'    => $earned,
                'paid'      => $paid,
                'balance'   => $balance,
            ];
        })->sortByDesc('balance')->values();

        // Full payout history
        $payoutHistory = VendorPayout::with('vendor')
            ->orderByDesc('paid_at')
            ->get();

        // Pending vendor payout requests (for the admin panel)
        $pendingPayoutRequests = VendorPayoutRequest::with('vendor')
            ->where('status', 'pending')
            ->latest()
            ->get();

        return view('admin.finance.payouts', [
            'vendorData'            => $vendorData,
            'payoutHistory'         => $payoutHistory,
            'payoutRate'            => $payoutRate,
            'pendingPayoutRequests' => $pendingPayoutRequests,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', VendorPayout::class);

        $request->validate([
            'user_id'           => 'required|exists:users,id',
            'amount'            => 'required|numeric|min:0.01',
            'reference_id'      => 'nullable|string|max:255',
            'notes'             => 'nullable|string|max:500',
            'payout_request_id' => 'nullable|exists:vendor_payout_requests,id',
        ]);

        // G4 — Guard: never record a payout larger than the vendor's current balance.
        $payoutRate = config('services.portal.vendor_payout_per_order');
        $vendor     = User::findOrFail($request->user_id);
        $rate       = $vendor->payout_rate ?? $payoutRate;
        $earned     = $vendor->delivered_orders_count * $rate;
        $paid       = VendorPayout::where('user_id', $vendor->id)->sum('amount');
        $balance    = $earned - $paid;

        if ($request->amount > $balance) {
            return back()->with(
                'error',
                "Cannot record payout: ₹" . number_format($request->amount, 0) .
                " exceeds {$vendor->name}'s current balance of ₹" . number_format($balance, 0) . "."
            );
        }

        VendorPayout::create([
            'user_id'      => $request->user_id,
            'amount'       => $request->amount,
            'reference_id' => $request->reference_id,
            'notes'        => $request->notes,
            'paid_at'      => now(),
        ]);

        // G5 — If this payout fulfils a vendor's self-submitted request, mark it done.
        if ($request->filled('payout_request_id')) {
            VendorPayoutRequest::where('id', $request->payout_request_id)
                ->where('status', 'pending')
                ->update(['status' => 'fulfilled', 'fulfilled_at' => now()]);
        }

        return back()->with('success', 'Payout of ₹' . number_format($request->amount, 0) . ' recorded successfully.');
    }

    /**
     * G5 — Vendor submits a payout request for their current balance.
     */
    public function requestPayout(Request $request)
    {
        $vendor = Auth::user();

        // Prevent a duplicate pending request.
        if (VendorPayoutRequest::where('user_id', $vendor->id)->where('status', 'pending')->exists()) {
            return back()->with('error', 'You already have a pending payout request. Please wait for the admin to process it.');
        }

        $payoutRate = config('services.portal.vendor_payout_per_order');
        $rate       = $vendor->payout_rate ?? $payoutRate;
        $earned     = $vendor->delivered_orders_count * $rate;
        $paid       = VendorPayout::where('user_id', $vendor->id)->sum('amount');
        $balance    = $earned - $paid;

        if ($balance <= 0) {
            return back()->with('error', 'Your current balance is ₹0. There is nothing to request.');
        }

        VendorPayoutRequest::create([
            'user_id'          => $vendor->id,
            'amount_requested' => $balance,
            'status'           => 'pending',
        ]);

        app(PortalTelegramAlertService::class)->notifyVendorPayoutRequested($vendor, $balance);

        return back()->with('success', 'Payout request of ₹' . number_format($balance, 0) . ' submitted. The admin will process it shortly.');
    }
}
