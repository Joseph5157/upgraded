<?php

namespace App\Filament\Client\Widgets;

use App\Enums\OrderStatus;
use Filament\Widgets\Widget;

class CreditOverviewWidget extends Widget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected static string $view = 'filament.client.widgets.credit-overview';

    protected function getViewData(): array
    {
        $client = auth()->user()?->client;

        if (! $client) {
            return ['balance' => 0, 'total' => 0, 'inProgress' => 0, 'completed' => 0];
        }

        return [
            'balance'    => $client->credit_balance ?? 0,
            'total'      => $client->orders()->where('status', '!=', OrderStatus::Cancelled->value)->count(),
            'inProgress' => $client->orders()->whereIn('status', [
                OrderStatus::Pending->value,
                OrderStatus::Claimed->value,
                OrderStatus::Processing->value,
            ])->count(),
            'completed'  => $client->orders()->where('status', OrderStatus::Delivered->value)->count(),
        ];
    }
}
