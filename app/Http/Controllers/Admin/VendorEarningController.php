<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\VendorEarningTransaction;
use App\Services\Finance\VendorEarningService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class VendorEarningController extends Controller
{
    public function __construct(protected VendorEarningService $earningService) {}

    /**
     * List delivered orders that have a posted pending_order_earning
     * but have not yet been approved or rejected.
     */
    public function index()
    {
        $pendingOrders = Order::whereHas('vendorEarningTransactions', function ($q) {
                $q->where('type', VendorEarningTransaction::TYPE_PENDING_ORDER_EARNING)
                  ->where('status', VendorEarningTransaction::STATUS_POSTED);
            })
            ->whereDoesntHave('vendorEarningTransactions', function ($q) {
                $q->whereIn('type', [
                    VendorEarningTransaction::TYPE_APPROVE_EARNING,
                    VendorEarningTransaction::TYPE_REVERSAL,
                ])->where('status', VendorEarningTransaction::STATUS_POSTED);
            })
            ->with(['vendor', 'client', 'vendorEarningTransactions' => function ($q) {
                $q->where('type', VendorEarningTransaction::TYPE_PENDING_ORDER_EARNING)
                  ->where('status', VendorEarningTransaction::STATUS_POSTED);
            }])
            ->latest('delivered_at')
            ->get();

        return view('admin.finance.vendor-earnings.pending', compact('pendingOrders'));
    }

    /**
     * Approve a vendor's pending earning — moves amount to approved_payable_balance.
     */
    public function approve(Request $request, Order $order): RedirectResponse
    {
        $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        $tx = $this->earningService->approveEarning(
            $order,
            Auth::user(),
            $request->notes ?: null,
        );

        Cache::forget('admin_nav_pending_vendor_earnings');

        $message = $tx
            ? 'Vendor earning approved and moved to payable balance.'
            : 'Nothing to approve — no pending earning found or already approved.';

        return back()->with('success', $message);
    }

    /**
     * Reject a vendor's pending earning — reverses amount from pending_earning_balance.
     */
    public function reject(Request $request, Order $order): RedirectResponse
    {
        $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $tx = $this->earningService->reverseEarning(
                $order,
                Auth::user(),
                $request->notes ?: null,
            );
        } catch (\LogicException $e) {
            return back()->with('error', $e->getMessage());
        }

        Cache::forget('admin_nav_pending_vendor_earnings');

        $message = $tx
            ? 'Vendor earning rejected and reversed from pending balance.'
            : 'Nothing to reject — no pending earning found or already reversed.';

        return back()->with('success', $message);
    }
}
