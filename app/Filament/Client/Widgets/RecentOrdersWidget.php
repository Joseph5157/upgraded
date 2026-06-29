<?php

namespace App\Filament\Client\Widgets;

use App\Enums\OrderStatus;
use App\Models\Order;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentOrdersWidget extends BaseWidget
{
    protected static bool $isLazy = true;

    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = 'Recent Orders';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Order #')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ucfirst(
                        $state instanceof OrderStatus ? $state->value : $state
                    ))
                    ->color(fn ($state): string => match (
                        $state instanceof OrderStatus ? $state->value : $state
                    ) {
                        'pending'    => 'gray',
                        'claimed'    => 'info',
                        'processing' => 'warning',
                        'delivered'  => 'success',
                        'cancelled'  => 'danger',
                        'failed'     => 'danger',
                        default      => 'gray',
                    }),
                Tables\Columns\TextColumn::make('files_count')
                    ->label('Files'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Submitted')
                    ->date('d M Y'),
            ])
            ->defaultSort('id', 'desc')
            ->paginated(false);
    }

    protected function getTableQuery(): Builder
    {
        $client = auth()->user()?->client;

        if (! $client) {
            return Order::query()->whereRaw('1 = 0');
        }

        return Order::query()
            ->where('client_id', $client->id)
            ->latest('id')
            ->limit(5);
    }
}
