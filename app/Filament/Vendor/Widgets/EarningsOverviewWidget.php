<?php

namespace App\Filament\Vendor\Widgets;

use Filament\Widgets\Widget;

class EarningsOverviewWidget extends Widget
{
    protected static bool $isLazy = true;

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected static string $view = 'filament.vendor.widgets.earnings-overview';

    protected function getViewData(): array
    {
        $user = auth()->user();

        return [
            'pending'  => $user ? ($user->pending_earning_balance ?? 0) : 0,
            'approved' => $user ? ($user->approved_payable_balance ?? 0) : 0,
            'today'    => $user ? ($user->daily_delivered_count ?? 0) : 0,
            'total'    => $user ? ($user->delivered_orders_count ?? 0) : 0,
        ];
    }
}
