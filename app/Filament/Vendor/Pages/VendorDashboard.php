<?php

namespace App\Filament\Vendor\Pages;

use App\Filament\Vendor\Widgets\AssignedOrdersWidget;
use App\Filament\Vendor\Widgets\EarningsOverviewWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class VendorDashboard extends BaseDashboard
{
    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'My Dashboard';

    public function getWidgets(): array
    {
        return [
            EarningsOverviewWidget::class,
            AssignedOrdersWidget::class,
        ];
    }

    public function getColumns(): int|string|array
    {
        return 1;
    }
}
