<?php

namespace App\Filament\Finance\Resources;

use App\Filament\Finance\Resources\ClientPaymentResource\Pages;
use App\Models\Client;
use App\Models\ClientPayment;
use App\Services\Finance\ClientPaymentService;
use App\Services\Finance\FinanceVoidService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ClientPaymentResource extends Resource
{
    protected static ?string $model = ClientPayment::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Client Finance';

    protected static ?int $navigationSort = 1;

    protected static ?string $label = 'Client Payment';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Payment Details')
                    ->schema([
                        Forms\Components\Select::make('client_id')
                            ->label('Client')
                            ->options(fn () => Client::orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->required()
                            ->disabled(fn (string $operation) => $operation === 'edit'),
                        Forms\Components\TextInput::make('amount_received')
                            ->label('Amount Received (₹)')
                            ->numeric()
                            ->required()
                            ->minValue(0.01)
                            ->step(0.01),
                        Forms\Components\TextInput::make('credits_added')
                            ->label('Credits to Add')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->integer(),
                        Forms\Components\Select::make('payment_mode')
                            ->options([
                                ClientPayment::MODE_UPI => 'UPI',
                                ClientPayment::MODE_BANK_TRANSFER => 'Bank Transfer',
                                ClientPayment::MODE_CASH => 'Cash',
                                ClientPayment::MODE_RAZORPAY => 'Razorpay',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('transaction_id')
                            ->label('Transaction Reference')
                            ->maxLength(255),
                        Forms\Components\DateTimePicker::make('received_at')
                            ->label('Payment Date')
                            ->required()
                            ->default(now()),
                        Forms\Components\Textarea::make('notes')
                            ->maxLength(1000)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

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
                Tables\Columns\TextColumn::make('amount_received')
                    ->label('Amount')
                    ->formatStateUsing(fn ($state) => $state !== null ? '₹ ' . number_format((float) $state, 2) : '—')
                    ->sortable(),
                Tables\Columns\TextColumn::make('credits_added')
                    ->label('Credits')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('rate_per_credit')
                    ->label('Rate/Credit')
                    ->formatStateUsing(fn ($state) => $state !== null ? '₹ ' . number_format((float) $state, 2) : '—')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('payment_mode')
                    ->label('Mode')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'upi' => 'success',
                        'bank_transfer' => 'info',
                        'cash' => 'warning',
                        'razorpay' => 'primary',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('transaction_id')
                    ->label('Reference')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'confirmed' => 'success',
                        'voided' => 'danger',
                        'refunded' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label('Received By')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('received_at')
                    ->label('Payment Date')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('client_id')
                    ->label('Client')
                    ->relationship('client', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('payment_mode')
                    ->options([
                        'upi' => 'UPI',
                        'bank_transfer' => 'Bank Transfer',
                        'cash' => 'Cash',
                        'razorpay' => 'Razorpay',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'confirmed' => 'Confirmed',
                        'voided' => 'Voided',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('void')
                    ->label('Void')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Void Payment')
                    ->modalDescription('This will reverse the credits added by this payment. This cannot be undone.')
                    ->form([
                        Forms\Components\Textarea::make('void_reason')
                            ->label('Reason for Voiding')
                            ->required()
                            ->maxLength(500),
                    ])
                    ->visible(fn (ClientPayment $record): bool => $record->status === ClientPayment::STATUS_CONFIRMED)
                    ->action(function (ClientPayment $record, array $data) {
                        try {
                            app(FinanceVoidService::class)->voidClientPayment(
                                $record,
                                auth()->user(),
                                $data['void_reason'],
                            );
                            Notification::make()->title('Payment voided successfully.')->success()->send();
                        } catch (\RuntimeException $e) {
                            Notification::make()->title('Cannot void payment')->body($e->getMessage())->danger()->send();
                        }
                    }),
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
            'index' => Pages\ListClientPayments::route('/'),
            'create' => Pages\CreateClientPayment::route('/create'),
            'view' => Pages\ViewClientPayment::route('/{record}'),
        ];
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
