<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Client;
use App\Models\Order;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AdminOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Orders', Order::count()),
            Stat::make('Active Clients', Client::where('status', 'active')->count()),
            Stat::make('Active Vendors', User::where('role', 'vendor')->active()->count()),
        ];
    }
}
