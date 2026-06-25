<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\TopupRequestResource\Pages;
use App\Models\TopupRequest;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TopupRequestResource extends Resource
{
    protected static ?string $model = TopupRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Topup Requests';

    protected static ?string $navigationGroup = 'Requests';

    protected static ?int $navigationSort = 10;

    protected static ?string $label = 'Topup Request';

    protected static ?string $pluralLabel = 'Topup Requests';

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

    public static function getNavigationBadge(): ?string
    {
        $count = TopupRequest::where('status', 'pending')->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Request Details')
                    ->schema([
                        TextEntry::make('id')
                            ->label('Request #'),
                        TextEntry::make('client.name')
                            ->label('Client'),
                        TextEntry::make('amount_requested')
                            ->label('Amount Requested'),
                        TextEntry::make('amount_paid')
                            ->label('Amount Paid')
                            ->placeholder('—'),
                        TextEntry::make('transaction_id')
                            ->label('Transaction ID')
                            ->placeholder('—'),
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn ($state): string => match ((string) $state) {
                                'approved' => 'success',
                                'rejected' => 'danger',
                                'pending'  => 'warning',
                                default    => 'gray',
                            })
                            ->formatStateUsing(fn ($state): string => ucfirst((string) $state)),
                        TextEntry::make('notes')
                            ->placeholder('—'),
                        TextEntry::make('created_at')
                            ->label('Submitted')
                            ->dateTime(),
                        TextEntry::make('reviewed_at')
                            ->label('Reviewed')
                            ->dateTime()
                            ->placeholder('—'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->sortable(),

                Tables\Columns\TextColumn::make('client.name')
                    ->label('Client')
                    ->searchable(),

                Tables\Columns\TextColumn::make('amount_requested')
                    ->label('Requested'),

                Tables\Columns\TextColumn::make('transaction_id')
                    ->label('Txn ID')
                    ->placeholder('—')
                    ->limit(20),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state): string => match ((string) $state) {
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'pending'  => 'warning',
                        default    => 'gray',
                    })
                    ->formatStateUsing(fn ($state): string => ucfirst((string) $state)),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Submitted')
                    ->dateTime('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('reviewed_at')
                    ->label('Reviewed')
                    ->dateTime('d M Y')
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending'  => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc')
            ->bulkActions([])
            ->paginated([10, 25, 50]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTopupRequests::route('/'),
            'view'  => Pages\ViewTopupRequest::route('/{record}'),
        ];
    }
}
