<?php

namespace App\Filament\Finance\Resources;

use App\Filament\Finance\Resources\BusinessExpenseResource\Pages;
use App\Models\BusinessExpense;
use App\Services\Finance\FinanceVoidService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BusinessExpenseResource extends Resource
{
    protected static ?string $model = BusinessExpense::class;

    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';

    protected static ?string $navigationGroup = 'Expenses';

    protected static ?int $navigationSort = 1;

    protected static ?string $label = 'Business Expense';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Expense Details')
                    ->schema([
                        Forms\Components\Select::make('category')
                            ->options(BusinessExpense::categories())
                            ->required()
                            ->searchable(),
                        Forms\Components\TextInput::make('amount')
                            ->label('Amount (₹)')
                            ->numeric()
                            ->required()
                            ->minValue(0.01)
                            ->step(0.01),
                        Forms\Components\Select::make('payment_mode')
                            ->options([
                                'upi' => 'UPI',
                                'bank_transfer' => 'Bank Transfer',
                                'cash' => 'Cash',
                                'auto_deducted' => 'Auto Deducted',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('reference_id')
                            ->label('Reference Number')
                            ->maxLength(255),
                        Forms\Components\DatePicker::make('expense_date')
                            ->label('Expense Date')
                            ->required()
                            ->default(today()),
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
                Tables\Columns\TextColumn::make('expense_date')
                    ->label('Date')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('category')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => BusinessExpense::categories()[$state] ?? ucfirst($state))
                    ->color('primary'),
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
                        'auto_deducted' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('reference_id')
                    ->label('Reference')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'active' => 'success',
                        'voided' => 'danger',
                        default => 'success',
                    }),
                Tables\Columns\TextColumn::make('notes')
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label('Created By')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->options(BusinessExpense::categories()),
                Tables\Filters\SelectFilter::make('payment_mode')
                    ->options([
                        'upi' => 'UPI',
                        'bank_transfer' => 'Bank Transfer',
                        'cash' => 'Cash',
                        'auto_deducted' => 'Auto Deducted',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
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
                    ->modalHeading('Void Expense')
                    ->modalDescription('This will mark the expense as voided. It will no longer be counted in reports.')
                    ->form([
                        Forms\Components\Textarea::make('void_reason')
                            ->label('Reason for Voiding')
                            ->required()
                            ->maxLength(500),
                    ])
                    ->visible(fn (BusinessExpense $record): bool => ($record->status ?? 'active') !== BusinessExpense::STATUS_VOIDED)
                    ->action(function (BusinessExpense $record, array $data) {
                        app(FinanceVoidService::class)->voidBusinessExpense(
                            $record,
                            auth()->user(),
                            $data['void_reason'],
                        );
                        Notification::make()->title('Expense voided successfully.')->success()->send();
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
            'index' => Pages\ListBusinessExpenses::route('/'),
            'create' => Pages\CreateBusinessExpense::route('/create'),
            'view' => Pages\ViewBusinessExpense::route('/{record}'),
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
