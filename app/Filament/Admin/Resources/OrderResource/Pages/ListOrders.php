<?php

namespace App\Filament\Admin\Resources\OrderResource\Pages;

use App\Enums\OrderStatus;
use App\Filament\Admin\Resources\OrderResource;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All'),
            'pending' => Tab::make('Pending')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', OrderStatus::Pending->value))
                ->badge(fn () => \App\Models\Order::where('status', OrderStatus::Pending->value)->count())
                ->badgeColor('warning'),
            'claimed' => Tab::make('Claimed')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', OrderStatus::Claimed->value)),
            'processing' => Tab::make('Processing')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', OrderStatus::Processing->value)),
            'delivered' => Tab::make('Delivered')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', OrderStatus::Delivered->value))
                ->badgeColor('success'),
            'failed' => Tab::make('Failed')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', OrderStatus::Failed->value))
                ->badgeColor('danger'),
        ];
    }
}
