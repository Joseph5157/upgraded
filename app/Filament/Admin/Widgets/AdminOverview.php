<?php

namespace App\Filament\Admin\Widgets;

use App\Enums\OrderStatus;
use App\Models\Client;
use App\Models\Order;
use App\Models\RefundRequest;
use App\Models\TopupRequest;
use App\Models\User;
use Filament\Widgets\Widget;

class AdminOverview extends Widget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected static string $view = 'filament.admin.widgets.admin-overview';

    protected function getViewData(): array
    {
        return [
            'totalOrders'     => Order::count(),
            'pendingOrders'   => Order::where('status', OrderStatus::Pending->value)->count(),
            'activeClients'   => Client::where('status', 'active')->count(),
            'activeVendors'   => User::where('role', 'vendor')->where('status', 'active')->count(),
            'pendingRequests' => TopupRequest::where('status', 'pending')->count()
                               + RefundRequest::where('status', 'pending')->count(),
            'deliveredToday'  => Order::where('status', OrderStatus::Delivered->value)
                                    ->whereDate('delivered_at', today())->count(),
        ];
    }
}
