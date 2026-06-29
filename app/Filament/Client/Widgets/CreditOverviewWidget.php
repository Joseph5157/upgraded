<?php

namespace App\Filament\Client\Widgets;

use App\Enums\OrderStatus;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

class CreditOverviewWidget extends Widget
{
    protected static bool $isLazy = true;

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected static string $view = 'filament.client.widgets.credit-overview';

    protected function getViewData(): array
    {
        $client = auth()->user()?->client;

        if (! $client) {
            return ['balance' => 0, 'total' => 0, 'inProgress' => 0, 'completed' => 0];
        }

        // Single query to get all status counts instead of 3 separate queries
        $counts = $client->orders()
            ->select('status', DB::raw('COUNT(*) as cnt'))
            ->groupBy('status')
            ->pluck('cnt', 'status');

        $inProgress = ($counts[OrderStatus::Pending->value] ?? 0)
            + ($counts[OrderStatus::Claimed->value] ?? 0)
            + ($counts[OrderStatus::Processing->value] ?? 0);

        $total = $counts->reject(fn ($cnt, $status) => $status === OrderStatus::Cancelled->value)->sum();

        return [
            'balance'    => $client->credit_balance ?? 0,
            'total'      => $total,
            'inProgress' => $inProgress,
            'completed'  => $counts[OrderStatus::Delivered->value] ?? 0,
        ];
    }
}
