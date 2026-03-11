<?php

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Models\DailyLedger;
use App\Models\Order;
use Illuminate\Console\Command;

class CloseDayCommand extends Command
{
    protected $signature = 'app:close-day {--date= : Date to close (Y-m-d). Defaults to today.}';
    protected $description = 'Aggregate delivered orders into a daily ledger snapshot.';

    public function handle(): int
    {
        $payoutRate   = config('services.portal.vendor_payout_per_order');
        $defaultPrice = config('services.portal.default_client_price');

        $date = $this->option('date')
            ? \Carbon\Carbon::parse($this->option('date'))->toDateString()
            : today()->toDateString();

        $this->info("Closing day: {$date}");

        $orders = Order::where('status', OrderStatus::Delivered)
            ->whereDate('delivered_at', $date)
            ->with(['client', 'vendor'])
            ->get();

        if ($orders->isEmpty()) {
            $this->warn("No delivered orders found for {$date}.");
        }

        // Revenue: sum each client's price_per_file
        $totalRevenue      = $orders->sum(fn($o) => $o->client?->price_per_file ?? $defaultPrice);
        $vendorPayouts     = $orders->count() * $payoutRate;
        $operationalCosts  = 0; // placeholder for future overhead costs
        $netProfit         = $totalRevenue - $vendorPayouts - $operationalCosts;

        // Client breakdown
        $clientBreakdown = $orders->groupBy('client_id')->map(function ($clientOrders) use ($defaultPrice) {
            $client      = $clientOrders->first()->client;
            $pricePerFile = $client?->price_per_file ?? $defaultPrice;
            return [
                'id'            => $client?->id,
                'name'          => $client?->name ?? 'Unknown',
                'orders'        => $clientOrders->count(),
                'price_per_file' => $pricePerFile,
                'revenue'       => $clientOrders->count() * $pricePerFile,
            ];
        })->values()->toArray();

        // Vendor breakdown
        $vendorBreakdown = $orders->whereNotNull('claimed_by')->groupBy('claimed_by')->map(function ($vendorOrders) use ($payoutRate) {
            $vendor = $vendorOrders->first()->vendor;
            return [
                'id'     => $vendor?->id,
                'name'   => $vendor?->name ?? 'Unknown',
                'orders' => $vendorOrders->count(),
                'payout' => $vendorOrders->count() * $payoutRate,
            ];
        })->values()->toArray();

        DailyLedger::updateOrCreate(
            ['date' => $date],
            [
                'total_revenue'     => $totalRevenue,
                'vendor_payouts'    => $vendorPayouts,
                'operational_costs' => $operationalCosts,
                'net_profit'        => $netProfit,
                'client_breakdown'  => $clientBreakdown,
                'vendor_breakdown'  => $vendorBreakdown,
                'total_orders'      => $orders->count(),
            ]
        );

        $this->table(
            ['Date', 'Orders', 'Revenue', 'Payouts', 'Net Profit'],
            [[$date, $orders->count(), '₹' . $totalRevenue, '₹' . $vendorPayouts, '₹' . $netProfit]]
        );

        // Save per-vendor daily snapshot before resetting counter
        $payoutRate = config('services.portal.vendor_payout_per_order', 30);
        \App\Models\User::where('role', 'vendor')
            ->where('daily_delivered_count', '>', 0)
            ->each(function ($vendor) use ($date, $payoutRate) {
                \App\Models\VendorDailySnapshot::updateOrCreate(
                    ['user_id' => $vendor->id, 'date' => $date],
                    [
                        'orders_delivered' => $vendor->daily_delivered_count,
                        'amount_earned'    => $vendor->daily_delivered_count * $payoutRate,
                    ]
                );
            });

        // Reset daily delivered counter for all vendors
        \App\Models\User::where('role', 'vendor')->update(['daily_delivered_count' => 0]);
        $this->info('Daily snapshots saved and counters reset for all vendors.');

        $this->info('Day closed successfully.');
        return Command::SUCCESS;
    }
}
