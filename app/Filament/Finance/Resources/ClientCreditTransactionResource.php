<?php

namespace App\Filament\Finance\Resources;

use App\Filament\Finance\Resources\ClientCreditTransactionResource\Pages;
use App\Models\ClientCreditTransaction;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ClientCreditTransactionResource extends Resource
{
    protected static ?string $model = ClientCreditTransaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Client Finance';

    protected static ?int $navigationSort = 2;

    protected static ?string $label = 'Credit Ledger';

    protected static ?string $pluralLabel = 'Credit Ledger';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('client.name')
                    ->label('Client')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'payment_credit' => 'success',
                        'order_debit' => 'danger',
                        'refund_credit' => 'warning',
                        'opening_balance' => 'info',
                        'manual_adjustment' => 'primary',
                        'correction' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => str_replace('_', ' ', ucfirst($state))),
                Tables\Columns\TextColumn::make('credits_delta')
                    ->label('Credits')
                    ->numeric()
                    ->sortable()
                    ->color(fn (int $state): string => $state >= 0 ? 'success' : 'danger'),
                Tables\Columns\TextColumn::make('balance_after')
                    ->label('Balance After')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('rate_per_credit')
                    ->label('Rate')
                    ->money('INR')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('money_value')
                    ->label('Money Value')
                    ->money('INR')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('order_id')
                    ->label('Order')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('clientPayment.id')
                    ->label('Payment #')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('notes')
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label('Created By')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('client_id')
                    ->label('Client')
                    ->relationship('client', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'payment_credit' => 'Payment Credit',
                        'order_debit' => 'Order Debit',
                        'refund_credit' => 'Refund Credit',
                        'opening_balance' => 'Opening Balance',
                        'manual_adjustment' => 'Manual Adjustment',
                        'correction' => 'Correction',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClientCreditTransactions::route('/'),
            'view' => Pages\ViewClientCreditTransaction::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }
}
