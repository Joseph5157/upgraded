<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\DailyLedger;
use App\Models\Order;

class LedgerController extends Controller
{
    public function index()
    {
        $payoutRate   = config('services.portal.vendor_payout_per_order');
        $defaultPrice = config('services.portal.default_client_price');

        $today = today();

        $todayOrders = Order::where('status', OrderStatus::Delivered)
            ->whereDate('delivered_at', $today)
            ->with('client')
            ->get();

        $todayRevenue = $todayOrders->sum(fn($o) => $o->client?->price_per_file ?? $defaultPrice);
        $todayPayouts = $todayOrders->count() * $payoutRate;
        $todayProfit  = $todayRevenue - $todayPayouts;

        $ledgers = DailyLedger::orderByDesc('date')->paginate(30);

        return view('admin.finance.ledger', compact(
            'todayRevenue',
            'todayPayouts',
            'todayProfit',
            'todayOrders',
            'ledgers'
        ));
    }
}
