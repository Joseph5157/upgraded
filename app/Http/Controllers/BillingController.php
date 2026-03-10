<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\DailyLedger;
use App\Models\Order;
use App\Models\Client;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    const VENDOR_PAYOUT_PER_ORDER = 50; // ₹50 per order

    public function index()
    {
        $payoutRate = config('services.portal.vendor_payout_per_order');
        $defaultPrice = config('services.portal.default_client_price');

        // Today's real-time snapshot
        $today = today();
        $todayOrders = Order::where('status', OrderStatus::Delivered)
            ->whereDate('delivered_at', $today)
            ->with(['client', 'vendor'])
            ->get();

        $todayRevenue = $todayOrders->sum(fn($o) => $o->client?->price_per_file ?? $defaultPrice);
        $todayPayouts = $todayOrders->count() * $payoutRate;
        $todayProfit = $todayRevenue - $todayPayouts;

        // Client breakdown for today
        $todayClientBreakdown = $todayOrders->groupBy('client_id')->map(function ($orders) use ($defaultPrice) {
            $client = $orders->first()->client;
            return [
                'name'    => $client?->name ?? 'Unknown',
                'orders'  => $orders->count(),
                'revenue' => $orders->count() * ($client?->price_per_file ?? $defaultPrice),
            ];
        })->values();

        // Historical ledger entries
        $ledgers = DailyLedger::orderByDesc('date')->paginate(20);

        return view('admin.billing.index', compact(
            'todayRevenue',
            'todayPayouts',
            'todayProfit',
            'todayClientBreakdown',
            'todayOrders',
            'ledgers'
        ));
    }

    public function show(DailyLedger $ledger)
    {
        return view('admin.billing.show', compact('ledger'));
    }
}
