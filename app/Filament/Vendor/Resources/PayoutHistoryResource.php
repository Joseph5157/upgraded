<?php

namespace App\Filament\Vendor\Resources;

use App\Filament\Vendor\Resources\PayoutHistoryResource\Pages;
use App\Models\VendorPayout;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PayoutHistoryResource extends Resource
{
    protected static ?string $model = VendorPayout::class;

    protected static ?string $navigationLabel = 'Payouts';

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?int $navigationSort = 4;

    protected static ?string $label = 'Payout';

    protected static ?string $pluralLabel = 'Payout History';

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        if (! $user) {
            return parent::getEloquentQuery()->whereRaw('1 = 0');
        }

        return parent::getEloquentQuery()->where('user_id', $user->id);
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
                Tables\Columns\TextColumn::make('paid_at')
                    ->label('Date')
                    ->dateTime('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount (₹)')
                    ->formatStateUsing(fn ($state): string => '₹' . number_format((float) $state, 2)),

                Tables\Columns\TextColumn::make('payment_mode')
                    ->label('Mode')
                    ->badge()
                    ->color(fn ($state): string => match((string) $state) {
                        'upi'           => 'success',
                        'bank_transfer' => 'info',
                        'cash'          => 'warning',
                        default         => 'gray',
                    })
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('reference_id')
                    ->label('Reference')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state): string => match((string) $state) {
                        'paid'   => 'success',
                        'voided' => 'danger',
                        default  => 'gray',
                    }),

                Tables\Columns\TextColumn::make('notes')
                    ->label('Notes')
                    ->placeholder('—')
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('id', 'desc')
            ->actions([])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayoutHistory::route('/'),
        ];
    }
}
