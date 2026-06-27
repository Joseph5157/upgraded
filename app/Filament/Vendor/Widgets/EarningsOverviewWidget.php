<?php

namespace App\Filament\Vendor\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class EarningsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getColumns(): int
    {
        return 2;
    }

    protected function getStats(): array
    {
        $user = auth()->user();

        $pending  = $user ? ($user->pending_earning_balance ?? 0) : 0;
        $approved = $user ? ($user->approved_payable_balance ?? 0) : 0;
        $today    = $user ? ($user->daily_delivered_count ?? 0) : 0;
        $total    = $user ? ($user->delivered_orders_count ?? 0) : 0;

        return [
            Stat::make('Pending Earnings', $pending . ' ₹')
                ->description('Awaiting admin approval')
                ->color('warning'),

            Stat::make('Approved Payable', $approved . ' ₹')
                ->description('Ready to pay out')
                ->color('success'),

            Stat::make('Delivered Today', $today)
                ->description("Today's completions")
                ->color('info'),

            Stat::make('Total Delivered', $total)
                ->description('All time')
                ->color('indigo'),
        ];
    }
}
