<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\RefundRequestResource\Pages;
use App\Models\RefundRequest;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class RefundRequestResource extends Resource
{
    protected static ?string $model = RefundRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-uturn-left';

    protected static ?string $navigationLabel = 'Refund Requests';

    protected static ?string $navigationGroup = 'Requests';

    protected static ?int $navigationSort = 11;

    protected static ?string $label = 'Refund Request';

    protected static ?string $pluralLabel = 'Refund Requests';

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
        $count = RefundRequest::where('status', 'pending')->count();

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
                        TextEntry::make('order_id')
                            ->label('Order #'),
                        TextEntry::make('user.name')
                            ->label('Submitted By'),
                        TextEntry::make('reason')
                            ->label('Reason')
                            ->columnSpanFull(),
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn ($state): string => match ((string) $state) {
                                'approved' => 'success',
                                'rejected' => 'danger',
                                'pending'  => 'warning',
                                default    => 'gray',
                            })
                            ->formatStateUsing(fn ($state): string => ucfirst((string) $state)),
                        TextEntry::make('admin_note')
                            ->label('Admin Note')
                            ->placeholder('—'),
                        TextEntry::make('created_at')
                            ->label('Submitted')
                            ->dateTime(),
                        TextEntry::make('resolved_at')
                            ->label('Resolved')
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

                Tables\Columns\TextColumn::make('order_id')
                    ->label('Order #'),

                Tables\Columns\TextColumn::make('reason')
                    ->limit(40),

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

                Tables\Columns\TextColumn::make('resolved_at')
                    ->label('Resolved')
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
            'index' => Pages\ListRefundRequests::route('/'),
            'view'  => Pages\ViewRefundRequest::route('/{record}'),
        ];
    }
}
