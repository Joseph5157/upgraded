<?php

namespace App\Filament\Vendor\Widgets;

use App\Enums\OrderStatus;
use App\Models\Order;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class AssignedOrdersWidget extends BaseWidget
{
    protected static bool $isLazy = true;

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Active Assignments';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Order::query()
                    ->where('claimed_by', auth()->user()?->id ?? 0)
                    ->whereIn('status', [OrderStatus::Claimed->value, OrderStatus::Processing->value])
                    ->latest('id')
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Order #')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof OrderStatus
                        ? ucfirst($state->value)
                        : ucfirst((string) $state)
                    )
                    ->color(fn ($state): string => match(
                        $state instanceof OrderStatus ? $state->value : (string) $state
                    ) {
                        'claimed'    => 'info',
                        'processing' => 'warning',
                        default      => 'gray',
                    }),

                Tables\Columns\TextColumn::make('files_count')
                    ->label('Files'),

                Tables\Columns\TextColumn::make('due_at')
                    ->label('Due')
                    ->date('d M Y')
                    ->placeholder('—'),
            ])
            ->paginated(false);
    }
}
