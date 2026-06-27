<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Widgets\AdminOverview;
use App\Filament\Admin\Widgets\RecentOrdersWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?string $title = 'Admin Dashboard';

    public function getWidgets(): array
    {
        return [
            AdminOverview::class,
            RecentOrdersWidget::class,
        ];
    }

    public function getColumns(): int|string|array
    {
        return 1;
    }
}
