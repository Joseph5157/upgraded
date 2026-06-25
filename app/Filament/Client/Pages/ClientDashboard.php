<?php

namespace App\Filament\Client\Pages;

use App\Filament\Client\Widgets\CreditOverviewWidget;
use App\Filament\Client\Widgets\RecentOrdersWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class ClientDashboard extends BaseDashboard
{
    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'My Dashboard';

    public function getWidgets(): array
    {
        return [
            CreditOverviewWidget::class,
            RecentOrdersWidget::class,
        ];
    }

    public function getColumns(): int | string | array
    {
        return 1;
    }
}
