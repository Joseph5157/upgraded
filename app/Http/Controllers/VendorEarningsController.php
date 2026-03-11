<?php

namespace App\Http\Controllers;

use App\Models\VendorDailySnapshot;
use App\Models\VendorPayout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class VendorEarningsController extends Controller
{
    public function index()
    {
        $vendor = Auth::user();

        // Daily earning rows
        $snapshots = VendorDailySnapshot::where('user_id', $vendor->id)
            ->orderByDesc('date')
            ->get();

        // Payout rows from admin
        $payouts = VendorPayout::where('user_id', $vendor->id)
            ->orderByDesc('paid_at')
            ->get();

        // Build unified bank statement — merge snapshots + payouts sorted by date desc
        $statement = collect();

        foreach ($snapshots as $snap) {
            $statement->push([
                'date'        => $snap->date,
                'type'        => 'earned',
                'description' => $snap->orders_delivered . ' ' . Str::plural('order', $snap->orders_delivered) . ' completed',
                'credit'      => $snap->amount_earned,
                'debit'       => null,
            ]);
        }

        foreach ($payouts as $payout) {
            $statement->push([
                'date'        => $payout->paid_at->toDateString(),
                'type'        => 'paid',
                'description' => 'Admin paid' . ($payout->reference_id ? ' · Ref: ' . $payout->reference_id : ''),
                'credit'      => null,
                'debit'       => $payout->amount,
            ]);
        }

        // Sort by date desc, earned before paid on same day
        $statement = $statement->sortByDesc('date')->values();

        // Running balance (calculate forward from oldest to newest, then reverse)
        $ordered   = $statement->reverse()->values();
        $balance   = 0;
        $withBalance = $ordered->map(function ($row) use (&$balance) {
            $balance += $row['credit'] ?? 0;
            $balance -= $row['debit'] ?? 0;
            return array_merge($row, ['balance' => $balance]);
        })->reverse()->values();

        $totalEarned  = $snapshots->sum('amount_earned');
        $totalPaid    = $payouts->sum('amount');
        $pendingPayout = max(0, $totalEarned - $totalPaid);

        return view('vendor.earnings', [
            'statement'     => $withBalance,
            'totalEarned'   => $totalEarned,
            'totalPaid'     => $totalPaid,
            'pendingPayout' => $pendingPayout,
            'totalOrders'   => $snapshots->sum('orders_delivered'),
        ]);
    }
}
