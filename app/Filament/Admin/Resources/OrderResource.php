<?php

namespace App\Filament\Admin\Resources;

use App\Enums\OrderStatus;
use App\Filament\Admin\Resources\OrderResource\Pages;
use App\Models\Client;
use App\Models\Order;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section as InfoSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Order Details')
                    ->schema([
                        Forms\Components\Select::make('client_id')
                            ->label('Client')
                            ->relationship('client', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->disabled(fn (string $operation) => $operation === 'edit'),
                        Forms\Components\Select::make('claimed_by')
                            ->label('Vendor')
                            ->options(fn () => User::where('role', 'vendor')->pluck('name', 'id'))
                            ->searchable()
                            ->nullable(),
                        Forms\Components\TextInput::make('files_count')
                            ->label('File Count')
                            ->numeric()
                            ->minValue(0)
                            ->disabled(fn (string $operation) => $operation === 'edit'),
                        Forms\Components\Select::make('status')
                            ->options(collect(OrderStatus::cases())->mapWithKeys(
                                fn (OrderStatus $s) => [$s->value => ucfirst($s->value)]
                            ))
                            ->required()
                            ->disabled(fn (string $operation) => $operation === 'edit'),
                        Forms\Components\Textarea::make('notes')
                            ->maxLength(1000)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Financial Snapshot')
                    ->schema([
                        Forms\Components\TextInput::make('credits_consumed')
                            ->numeric()
                            ->disabled(),
                        Forms\Components\TextInput::make('client_rate_per_file')
                            ->label('Client Rate/File')
                            ->numeric()
                            ->disabled(),
                        Forms\Components\TextInput::make('client_amount')
                            ->label('Client Amount')
                            ->numeric()
                            ->disabled(),
                        Forms\Components\TextInput::make('vendor_rate_per_file')
                            ->label('Vendor Rate/File')
                            ->numeric()
                            ->disabled(),
                        Forms\Components\TextInput::make('vendor_amount')
                            ->label('Vendor Amount')
                            ->numeric()
                            ->disabled(),
                        Forms\Components\TextInput::make('gross_profit')
                            ->numeric()
                            ->disabled(),
                    ])
                    ->columns(3)
                    ->visible(fn (string $operation) => $operation !== 'create'),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                InfoSection::make('Order Details')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('id')->label('Order #'),
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (OrderStatus $state): string => match ($state) {
                                OrderStatus::Pending    => 'warning',
                                OrderStatus::Claimed    => 'info',
                                OrderStatus::Processing => 'primary',
                                OrderStatus::Delivered  => 'success',
                                OrderStatus::Cancelled  => 'danger',
                                OrderStatus::Failed     => 'danger',
                            }),
                        TextEntry::make('client.name')->label('Client'),
                        TextEntry::make('vendor.name')->label('Vendor')->placeholder('Unassigned'),
                        TextEntry::make('files_count')->label('Files'),
                        TextEntry::make('source'),
                        TextEntry::make('created_at')->label('Created')->dateTime(),
                        TextEntry::make('delivered_at')->label('Delivered')->dateTime()->placeholder('—'),
                    ]),

                InfoSection::make('Financial Snapshot')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('credits_consumed')->label('Credits'),
                        TextEntry::make('client_amount')->label('Client Amount')->formatStateUsing(fn ($state) => $state !== null ? '₹ ' . number_format((float) $state, 2) : '—'),
                        TextEntry::make('vendor_amount')->label('Vendor Amount')->formatStateUsing(fn ($state) => $state !== null ? '₹ ' . number_format((float) $state, 2) : '—'),
                        TextEntry::make('gross_profit')->label('Gross Profit')->formatStateUsing(fn ($state) => $state !== null ? '₹ ' . number_format((float) $state, 2) : '—'),
                    ]),

                InfoSection::make(fn ($record): string => $record->status === OrderStatus::Failed
                        ? 'Failure Details'
                        : 'Previous Failure (Historical)'
                    )
                    ->visible(fn ($record): bool => ! empty($record->failed_at))
                    ->columns(2)
                    ->schema([
                        TextEntry::make('failed_at')->label('Failed At')->dateTime(),
                        TextEntry::make('failedBy.name')->label('Marked Failed By')->placeholder('—'),
                        TextEntry::make('failure_reason')
                            ->label('Failure Reason')
                            ->columnSpanFull()
                            ->placeholder('No reason recorded'),
                    ]),

                InfoSection::make('Notes')
                    ->visible(fn ($record): bool => ! empty($record->notes))
                    ->schema([
                        TextEntry::make('notes')->label('Notes')->columnSpanFull(),
                    ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['client', 'vendor', 'failedBy']);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Order #')
                    ->sortable(),
                Tables\Columns\TextColumn::make('client.name')
                    ->label('Client')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('vendor.name')
                    ->label('Vendor')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Unassigned'),
                Tables\Columns\TextColumn::make('files_count')
                    ->label('Files')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (OrderStatus $state): string => match ($state) {
                        OrderStatus::Pending => 'warning',
                        OrderStatus::Claimed => 'info',
                        OrderStatus::Processing => 'primary',
                        OrderStatus::Delivered => 'success',
                        OrderStatus::Cancelled => 'danger',
                        OrderStatus::Failed => 'danger',
                    }),
                Tables\Columns\TextColumn::make('source')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('credits_consumed')
                    ->label('Credits')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('delivered_at')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('id', 'desc')
            ->defaultPaginationPageOption(10)
            ->paginationPageOptions([10, 25, 50])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(collect(OrderStatus::cases())->mapWithKeys(
                        fn (OrderStatus $s) => [$s->value => ucfirst($s->value)]
                    )),
                Tables\Filters\SelectFilter::make('client_id')
                    ->label('Client')
                    ->relationship('client', 'name')
                    ->searchable(),
                Tables\Filters\SelectFilter::make('claimed_by')
                    ->label('Vendor')
                    ->options(fn () => User::where('role', 'vendor')->pluck('name', 'id'))
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListOrders::route('/'),
            'view' => Pages\ViewOrder::route('/{record}'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
