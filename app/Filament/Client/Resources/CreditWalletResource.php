<?php

namespace App\Filament\Client\Resources;

use App\Filament\Client\Resources\CreditWalletResource\Pages;
use App\Models\ClientCreditTransaction;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CreditWalletResource extends Resource
{
    protected static ?string $model = ClientCreditTransaction::class;

    protected static ?string $navigationLabel = 'Credit Wallet';

    protected static ?string $navigationIcon = 'heroicon-o-wallet';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationGroup = null;

    protected static ?string $label = 'Credit Transaction';

    protected static ?string $pluralLabel = 'Credit Wallet';

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
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string =>
                        ucwords(str_replace('_', ' ', $state))
                    )
                    ->color(fn (string $state): string => match ($state) {
                        'payment_credit'    => 'success',
                        'order_debit'       => 'danger',
                        'refund_credit'     => 'warning',
                        'opening_balance'   => 'info',
                        'manual_adjustment' => 'primary',
                        'correction'        => 'gray',
                        default             => 'gray',
                    }),
                Tables\Columns\TextColumn::make('credits_delta')
                    ->label('Credits')
                    ->numeric()
                    ->formatStateUsing(fn ($state): string =>
                        (int) $state >= 0 ? '+' . $state : (string) $state
                    )
                    ->color(fn ($state): string =>
                        (int) $state >= 0 ? 'success' : 'danger'
                    ),
                Tables\Columns\TextColumn::make('balance_after')
                    ->label('Balance After')
                    ->numeric(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->actions([])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCreditTransactions::route('/'),
        ];
    }
}
