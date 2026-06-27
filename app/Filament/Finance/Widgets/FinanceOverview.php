<?php

namespace App\Filament\Finance\Widgets;

use App\Services\Finance\FinanceDashboardService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FinanceOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    protected static bool $isLazy = true;

    protected function getStats(): array
    {
        $m = app(FinanceDashboardService::class)->metrics();

        $fmt = fn (float $v) => '₹' . number_format($v, 2);

        return [
            Stat::make('Money Received', $fmt($m['total_money_received']))
                ->description('Total confirmed payments')
                ->color('success'),

            Stat::make('Credits Added', number_format($m['credits_added']))
                ->description('From payments'),

            Stat::make('Credits Used', number_format($m['credits_used']))
                ->description('Deducted on upload'),

            Stat::make('Credits Remaining', number_format($m['credits_remaining']))
                ->description('Live client balances')
                ->color($m['credits_remaining'] > 0 ? 'success' : 'warning'),

            Stat::make('Vendor Payable', $fmt($m['vendor_payable']))
                ->description('Approved, not yet paid')
                ->color($m['vendor_payable'] > 0 ? 'warning' : 'success'),

            Stat::make('Vendor Paid', $fmt($m['vendor_paid']))
                ->description('Total payouts made'),

            Stat::make('Business Expenses', $fmt($m['business_expenses']))
                ->description('Non-voided expenses'),

            Stat::make('Gross Profit', $fmt($m['gross_profit']))
                ->description('Revenue - vendor cost')
                ->color($m['gross_profit'] >= 0 ? 'success' : 'danger'),

            Stat::make('Net Profit', $fmt($m['net_profit']))
                ->description('Gross profit - expenses')
                ->color($m['net_profit'] >= 0 ? 'success' : 'danger'),

            Stat::make('Cash Balance', $fmt($m['cash_balance']))
                ->description('Received - paid - expenses')
                ->color($m['cash_balance'] >= 0 ? 'success' : 'danger'),
        ];
    }
}
