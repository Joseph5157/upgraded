<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\User;
use App\Models\Order;
use App\Models\VendorPayout;
use Illuminate\Http\Request;

class VendorPayoutController extends Controller
{
    public function index()
    {
        $payoutRate = config('services.portal.vendor_payout_per_order');

        // All vendors
        $vendors = User::where('role', 'vendor')->get();

        // Compute balance for each vendor
        $vendorData = $vendors->map(function ($vendor) use ($payoutRate) {
            $delivered = Order::where('claimed_by', $vendor->id)
                ->where('status', OrderStatus::Delivered)
                ->count();

            $earned  = $delivered * $payoutRate;
            $paid    = VendorPayout::where('user_id', $vendor->id)->sum('amount');
            $balance = $earned - $paid;

            return [
                'vendor'    => $vendor,
                'delivered' => $delivered,
                'earned'    => $earned,
                'paid'      => $paid,
                'balance'   => $balance,
            ];
        })->sortByDesc('balance')->values();

        // Full payout history
        $payoutHistory = VendorPayout::with('vendor')
            ->orderByDesc('paid_at')
            ->get();

        return view('admin.finance.payouts', [
            'vendorData'    => $vendorData,
            'payoutHistory' => $payoutHistory,
            'payoutRate'    => $payoutRate,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:0.01',
            'reference_id' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:500',
        ]);

        VendorPayout::create([
            'user_id' => $request->user_id,
            'amount' => $request->amount,
            'reference_id' => $request->reference_id,
            'notes' => $request->notes,
            'paid_at' => now(),
        ]);

        return back()->with('success', 'Payout of ₹' . number_format($request->amount, 0) . ' recorded successfully.');
    }
}
