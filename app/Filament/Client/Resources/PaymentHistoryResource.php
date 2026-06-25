<?php

namespace App\Filament\Client\Resources;

use App\Filament\Client\Resources\PaymentHistoryResource\Pages;
use App\Models\ClientPayment;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PaymentHistoryResource extends Resource
{
    protected static ?string $model = ClientPayment::class;

    protected static ?string $navigationLabel = 'Payments';

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationGroup = null;

    protected static ?string $label = 'Payment';

    protected static ?string $pluralLabel = 'Payment History';

    public static function getEloquentQuery(): Builder
    {
        $client = auth()->user()?->client;

        if (! $client) {
            return parent::getEloquentQuery()->whereRaw('1 = 0');
        }

        return parent::getEloquentQuery()->where('client_id', $client->id);
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
                Tables\Columns\TextColumn::make('received_at')
                    ->label('Date')
                    ->dateTime('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount_received')
                    ->label('Amount')
                    ->money('INR'),
                Tables\Columns\TextColumn::make('payment_mode')
                    ->label('Mode')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'upi'           => 'success',
                        'bank_transfer' => 'info',
                        'cash'          => 'warning',
                        'razorpay'      => 'primary',
                        default         => 'gray',
                    }),
                Tables\Columns\TextColumn::make('credits_added')
                    ->label('Credits Added')
                    ->numeric(),
                Tables\Columns\TextColumn::make('transaction_id')
                    ->label('Reference')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'confirmed' => 'success',
                        'voided'    => 'danger',
                        'refunded'  => 'warning',
                        default     => 'gray',
                    }),
            ])
            ->defaultSort('id', 'desc')
            ->actions([])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPaymentHistory::route('/'),
        ];
    }
}
