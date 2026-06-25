<?php

namespace App\Filament\Finance\Resources;

use App\Filament\Finance\Resources\VendorPayoutResource\Pages;
use App\Models\User;
use App\Models\VendorPayout;
use App\Services\Finance\FinanceVoidService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class VendorPayoutResource extends Resource
{
    protected static ?string $model = VendorPayout::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-rupee';

    protected static ?string $navigationGroup = 'Vendor Finance';

    protected static ?int $navigationSort = 2;

    protected static ?string $label = 'Vendor Payout';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Payout Details')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Vendor')
                            ->options(fn () => User::where('role', 'vendor')->orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->required()
                            ->disabled(fn (string $operation) => $operation === 'edit')
                            ->helperText(function (?string $state) {
                                if (! $state) {
                                    return null;
                                }
                                $vendor = User::find($state);

                                return $vendor
                                    ? 'Approved payable: ₹' . number_format((float) $vendor->approved_payable_balance, 2)
                                    : null;
                            })
                            ->live(),
                        Forms\Components\TextInput::make('amount')
                            ->label('Payout Amount (₹)')
                            ->numeric()
                            ->required()
                            ->minValue(0.01)
                            ->step(0.01),
                        Forms\Components\Select::make('payment_mode')
                            ->options([
                                'upi' => 'UPI',
                                'bank_transfer' => 'Bank Transfer',
                                'cash' => 'Cash',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('transaction_id')
                            ->label('Transaction Reference')
                            ->maxLength(255),
                        Forms\Components\DateTimePicker::make('paid_at')
                            ->label('Payout Date')
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
                Tables\Columns\TextColumn::make('vendor.name')
                    ->label('Vendor')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->money('INR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_mode')
                    ->label('Mode')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'upi' => 'success',
                        'bank_transfer' => 'info',
                        'cash' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('reference_id')
                    ->label('Reference')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'paid' => 'success',
                        'voided' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('paidBy.name')
                    ->label('Paid By')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('paid_at')
                    ->label('Payout Date')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Vendor')
                    ->relationship('vendor', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('payment_mode')
                    ->options([
                        'upi' => 'UPI',
                        'bank_transfer' => 'Bank Transfer',
                        'cash' => 'Cash',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'paid' => 'Paid',
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
                    ->modalHeading('Void Payout')
                    ->modalDescription('This will restore the payout amount to the vendor\'s approved payable balance. This cannot be undone.')
                    ->form([
                        Forms\Components\Textarea::make('void_reason')
                            ->label('Reason for Voiding')
                            ->required()
                            ->maxLength(500),
                    ])
                    ->visible(fn (VendorPayout $record): bool => $record->status === 'paid')
                    ->action(function (VendorPayout $record, array $data) {
                        try {
                            app(FinanceVoidService::class)->voidVendorPayout(
                                $record,
                                auth()->user(),
                                $data['void_reason'],
                            );
                            Notification::make()->title('Payout voided successfully.')->success()->send();
                        } catch (\RuntimeException $e) {
                            Notification::make()->title('Cannot void payout')->body($e->getMessage())->danger()->send();
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
            'index' => Pages\ListVendorPayouts::route('/'),
            'create' => Pages\CreateVendorPayout::route('/create'),
            'view' => Pages\ViewVendorPayout::route('/{record}'),
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
