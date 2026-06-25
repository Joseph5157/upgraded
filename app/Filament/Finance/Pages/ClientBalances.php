<?php

namespace App\Filament\Finance\Pages;

use App\Models\Client;
use App\Models\ClientCreditTransaction;
use App\Models\ClientPayment;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class ClientBalances extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-scale';

    protected static ?string $navigationLabel = 'Client Balances';

    protected static ?string $navigationGroup = 'Client Finance';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Client Balances';

    protected static string $view = 'filament.finance.pages.client-balances';

    public function getTotalCreditsRemaining(): int
    {
        return (int) Client::where('status', '!=', 'deleted')->sum('credit_balance');
    }

    public function getTotalReceived(): float
    {
        return (float) ClientPayment::where('status', ClientPayment::STATUS_CONFIRMED)->sum('amount_received');
    }

    public function getTotalCreditsUsed(): int
    {
        return (int) abs(
            ClientCreditTransaction::where('type', 'order_debit')->sum('credits_delta')
        );
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Client::query()
                    ->where('status', '!=', 'deleted')
                    ->with('user')
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Client')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Client $record): string =>
                        $record->user?->portal_number ?? ''
                    ),

                Tables\Columns\TextColumn::make('credit_balance')
                    ->label('Balance')
                    ->numeric()
                    ->sortable()
                    ->color(fn ($state): string => (int) $state > 0 ? 'success' : 'gray'),

                Tables\Columns\TextColumn::make('total_received')
                    ->label('Total Received (₹)')
                    ->state(fn (Client $record): string =>
                        '₹' . number_format(
                            (float) $record->clientPayments()
                                ->where('status', ClientPayment::STATUS_CONFIRMED)
                                ->sum('amount_received'),
                            2
                        )
                    ),

                Tables\Columns\TextColumn::make('credits_added')
                    ->label('Credits Added')
                    ->state(fn (Client $record): int =>
                        (int) $record->clientPayments()
                            ->where('status', ClientPayment::STATUS_CONFIRMED)
                            ->sum('credits_added')
                    )
                    ->numeric(),

                Tables\Columns\TextColumn::make('credits_used')
                    ->label('Credits Used')
                    ->state(fn (Client $record): int =>
                        (int) abs(
                            $record->creditTransactions()
                                ->where('type', 'order_debit')
                                ->sum('credits_delta')
                        )
                    )
                    ->numeric()
                    ->color('danger'),

                Tables\Columns\TextColumn::make('credits_refunded')
                    ->label('Refunded')
                    ->state(fn (Client $record): int =>
                        (int) $record->creditTransactions()
                            ->where('type', 'refund_credit')
                            ->sum('credits_delta')
                    )
                    ->numeric()
                    ->color('warning')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('last_payment')
                    ->label('Last Payment')
                    ->state(fn (Client $record): string =>
                        $record->clientPayments()
                            ->where('status', ClientPayment::STATUS_CONFIRMED)
                            ->orderByDesc('received_at')
                            ->first()
                            ?->received_at
                            ?->format('d M Y') ?? '—'
                    )
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state): string => match ((string) $state) {
                        'active'    => 'success',
                        'suspended' => 'danger',
                        default     => 'gray',
                    }),
            ])
            ->defaultSort('name')
            ->actions([])
            ->bulkActions([])
            ->paginated([10, 25, 50]);
    }
}
