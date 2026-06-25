<?php

namespace App\Filament\Client\Pages;

use App\Models\TopupRequest;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class TopupRequests extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Topup Requests';

    protected static ?int $navigationSort = 7;

    protected static ?string $title = 'Topup Requests';

    protected static string $view = 'filament.client.pages.topup-requests';

    public function table(Table $table): Table
    {
        $client = auth()->user()?->client;
        $clientId = $client?->id ?? 0;

        return $table
            ->query(
                TopupRequest::query()->where('client_id', $clientId)
            )
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount_requested')
                    ->label('Amount Requested'),

                Tables\Columns\TextColumn::make('transaction_id')
                    ->label('Transaction ID')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state): string => match ((string) $state) {
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'pending'  => 'warning',
                        default    => 'gray',
                    })
                    ->formatStateUsing(fn ($state): string => ucfirst((string) $state)),

                Tables\Columns\TextColumn::make('notes')
                    ->label('Notes')
                    ->placeholder('—')
                    ->limit(40),

                Tables\Columns\TextColumn::make('reviewed_at')
                    ->label('Reviewed')
                    ->dateTime('d M Y')
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([])
            ->bulkActions([])
            ->emptyStateHeading('No topup requests')
            ->emptyStateDescription('Contact your admin to add credits to your account.')
            ->paginated([10, 25]);
    }
}
