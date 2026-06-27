<?php

namespace App\Filament\Finance\Resources;

use App\Filament\Finance\Resources\VendorEarningTransactionResource\Pages;
use App\Models\VendorEarningTransaction;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class VendorEarningTransactionResource extends Resource
{
    protected static ?string $model = VendorEarningTransaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-trending-up';

    protected static ?string $navigationGroup = 'Vendor Finance';

    protected static ?int $navigationSort = 1;

    protected static ?string $label = 'Vendor Earning';

    protected static ?string $pluralLabel = 'Vendor Earnings';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('vendor.name')
                    ->label('Vendor')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending_order_earning' => 'warning',
                        'approve_earning' => 'success',
                        'payout' => 'info',
                        'reversal' => 'danger',
                        'payout_reversal' => 'danger',
                        'manual_adjustment' => 'primary',
                        'correction' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => str_replace('_', ' ', ucfirst($state))),
                Tables\Columns\TextColumn::make('amount_delta')
                    ->label('Amount')
                    ->formatStateUsing(fn ($state) => $state !== null ? '₹ ' . number_format((float) $state, 2) : '—')
                    ->sortable()
                    ->color(fn (string $state): string => (float) $state >= 0 ? 'success' : 'danger'),
                Tables\Columns\TextColumn::make('files_count')
                    ->label('Files')
                    ->numeric()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('rate_per_file')
                    ->label('Rate/File')
                    ->formatStateUsing(fn ($state) => $state !== null ? '₹ ' . number_format((float) $state, 2) : '—')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('pending_balance_after')
                    ->label('Pending After')
                    ->formatStateUsing(fn ($state) => $state !== null ? '₹ ' . number_format((float) $state, 2) : '—')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('approved_balance_after')
                    ->label('Approved After')
                    ->formatStateUsing(fn ($state) => $state !== null ? '₹ ' . number_format((float) $state, 2) : '—')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'posted' => 'success',
                        'voided' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('order_id')
                    ->label('Order')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('vendorPayout.id')
                    ->label('Payout #')
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
                Tables\Filters\SelectFilter::make('vendor_id')
                    ->label('Vendor')
                    ->relationship('vendor', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'pending_order_earning' => 'Pending Order Earning',
                        'approve_earning' => 'Approve Earning',
                        'payout' => 'Payout',
                        'reversal' => 'Reversal',
                        'payout_reversal' => 'Payout Reversal',
                        'manual_adjustment' => 'Manual Adjustment',
                        'correction' => 'Correction',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'posted' => 'Posted',
                        'voided' => 'Voided',
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
            'index' => Pages\ListVendorEarningTransactions::route('/'),
            'view' => Pages\ViewVendorEarningTransaction::route('/{record}'),
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
