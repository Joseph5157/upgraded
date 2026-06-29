<?php

namespace App\Filament\Admin\Widgets;

use App\Enums\OrderStatus;
use App\Models\Order;
use Filament\Widgets\Widget;

class RecentOrdersWidget extends Widget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected static string $view = 'filament.admin.widgets.recent-orders';

    protected function getViewData(): array
    {
        return [
            'orders' => Order::with(['client', 'vendor'])
                ->latest()
                ->limit(8)
                ->get(),
        ];
    }
}
