<?php

namespace App\Filament\Vendor\Resources;

use App\Filament\Vendor\Resources\EarningHistoryResource\Pages;
use App\Models\VendorEarningTransaction;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EarningHistoryResource extends Resource
{
    protected static ?string $model = VendorEarningTransaction::class;

    protected static ?string $navigationLabel = 'Earnings';

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?int $navigationSort = 3;

    protected static ?string $label = 'Earning';

    protected static ?string $pluralLabel = 'Earning History';

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        if (! $user) {
            return parent::getEloquentQuery()->whereRaw('1 = 0');
        }

        return parent::getEloquentQuery()->where('vendor_id', $user->id);
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

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => ucwords(str_replace('_', ' ', $state)))
                    ->color(fn ($state): string => match((string) $state) {
                        'pending_order_earning' => 'warning',
                        'approve_earning'       => 'success',
                        'payout'                => 'info',
                        'reversal'              => 'danger',
                        'manual_adjustment'     => 'primary',
                        'payout_reversal'       => 'danger',
                        'correction'            => 'gray',
                        default                 => 'gray',
                    }),

                Tables\Columns\TextColumn::make('amount_delta')
                    ->label('Amount (₹)')
                    ->formatStateUsing(fn ($state): string => (float) $state >= 0
                        ? '+₹' . number_format((float) $state, 2)
                        : '-₹' . number_format(abs((float) $state), 2)
                    )
                    ->color(fn ($state): string => (float) $state >= 0 ? 'success' : 'danger'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state): string => match((string) $state) {
                        'posted' => 'success',
                        'voided' => 'danger',
                        default  => 'gray',
                    }),

                Tables\Columns\TextColumn::make('order_id')
                    ->label('Order #')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d M Y')
                    ->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->actions([])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEarningHistory::route('/'),
        ];
    }
}
