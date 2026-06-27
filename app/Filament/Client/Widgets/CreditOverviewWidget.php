<?php

namespace App\Filament\Client\Widgets;

use App\Enums\OrderStatus;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CreditOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected static string $view = 'filament.widgets.stats-2col';

    protected function getStats(): array
    {
        $client = auth()->user()?->client;

        if (! $client) {
            return [
                Stat::make('Credit Balance', '0 credits')
                    ->description('Available credits')
                    ->color('info'),
                Stat::make('Files Submitted', 0)
                    ->description('Total orders')
                    ->color('indigo'),
                Stat::make('In Progress', 0)
                    ->color('warning'),
                Stat::make('Completed', 0)
                    ->color('success'),
            ];
        }

        $totalOrders = $client->orders()
            ->where('status', '!=', OrderStatus::Cancelled->value)
            ->count();

        $inProgress = $client->orders()
            ->whereIn('status', [
                OrderStatus::Pending->value,
                OrderStatus::Claimed->value,
                OrderStatus::Processing->value,
            ])
            ->count();

        $completed = $client->orders()
            ->where('status', OrderStatus::Delivered->value)
            ->count();

        return [
            Stat::make('Credit Balance', ($client->credit_balance ?? 0) . ' credits')
                ->description('Available credits')
                ->color('info'),
            Stat::make('Files Submitted', $totalOrders)
                ->description('Total orders')
                ->color('indigo'),
            Stat::make('In Progress', $inProgress)
                ->color('warning'),
            Stat::make('Completed', $completed)
                ->color('success'),
        ];
    }
}
