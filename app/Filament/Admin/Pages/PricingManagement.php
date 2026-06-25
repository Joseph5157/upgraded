<?php

namespace App\Filament\Admin\Pages;

use App\Models\Client;
use App\Models\User;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class PricingManagement extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-currency-rupee';

    protected static ?string $navigationLabel = 'Pricing';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 30;

    protected static ?string $title = 'Pricing Management';

    protected static string $view = 'filament.admin.pages.pricing-management';

    public string $activeTab = 'clients';

    public function table(Table $table): Table
    {
        if ($this->activeTab === 'vendors') {
            return $this->vendorsTable($table);
        }

        return $this->clientsTable($table);
    }

    protected function clientsTable(Table $table): Table
    {
        return $table
            ->query(
                Client::query()->where('status', '!=', 'deleted')->orderBy('name')
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('price_per_file')
                    ->label('Rate Per File (₹)')
                    ->formatStateUsing(fn ($state): string => '₹' . number_format((float) $state, 2))
                    ->sortable(),

                Tables\Columns\TextColumn::make('credit_balance')
                    ->label('Credits')
                    ->numeric(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state): string => match ((string) $state) {
                        'active'    => 'success',
                        'suspended' => 'danger',
                        default     => 'gray',
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('edit_price')
                    ->label('Edit Rate')
                    ->icon('heroicon-o-pencil')
                    ->form([
                        TextInput::make('price_per_file')
                            ->label('Rate Per File (₹)')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->maxValue(99999)
                            ->default(fn (Client $record): float => (float) $record->price_per_file),
                    ])
                    ->action(function (Client $record, array $data): void {
                        $record->update(['price_per_file' => $data['price_per_file']]);

                        Notification::make()
                            ->success()
                            ->title("Price updated for {$record->name}.")
                            ->send();
                    }),
            ])
            ->bulkActions([])
            ->emptyStateHeading('No clients found')
            ->paginated([10, 25, 50]);
    }

    protected function vendorsTable(Table $table): Table
    {
        return $table
            ->query(
                User::query()
                    ->where('role', 'vendor')
                    ->whereNull('deleted_at')
                    ->orderBy('name')
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('portal_number')
                    ->label('Portal #'),

                Tables\Columns\TextColumn::make('payout_rate')
                    ->label('Payout Rate (₹)')
                    ->formatStateUsing(fn ($state): string => '₹' . number_format((float) $state, 2))
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state): string => match ((string) $state) {
                        'active' => 'success',
                        'frozen' => 'danger',
                        default  => 'gray',
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('edit_rate')
                    ->label('Edit Rate')
                    ->icon('heroicon-o-pencil')
                    ->form([
                        TextInput::make('payout_rate')
                            ->label('Payout Rate (₹)')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->maxValue(99999)
                            ->default(fn (User $record): float => (float) $record->payout_rate),
                    ])
                    ->action(function (User $record, array $data): void {
                        $record->update(['payout_rate' => $data['payout_rate']]);

                        Notification::make()
                            ->success()
                            ->title("Payout rate updated for {$record->name}.")
                            ->send();
                    }),
            ])
            ->bulkActions([])
            ->emptyStateHeading('No vendors found')
            ->paginated([10, 25, 50]);
    }
}
