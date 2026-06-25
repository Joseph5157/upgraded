<?php

namespace App\Filament\Vendor\Pages;

use App\Models\VendorPayout;
use App\Models\VendorPayoutRequest;
use App\Services\PortalTelegramAlertService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RequestPayout extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-currency-rupee';

    protected static ?string $navigationLabel = 'Request Payout';

    protected static ?int $navigationSort = 5;

    protected static ?string $title = 'Request Payout';

    protected static string $view = 'filament.vendor.pages.request-payout';

    public function getApprovedBalance(): float
    {
        return (float) (auth()->user()->approved_payable_balance ?? 0);
    }

    public function getPendingBalance(): float
    {
        return (float) (auth()->user()->pending_earning_balance ?? 0);
    }

    public function hasPendingRequest(): bool
    {
        return VendorPayoutRequest::where('user_id', auth()->id())
            ->where('status', 'pending')
            ->exists();
    }

    public function getPendingRequest(): ?VendorPayoutRequest
    {
        return VendorPayoutRequest::where('user_id', auth()->id())
            ->where('status', 'pending')
            ->first();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('requestPayout')
                ->label('Request Payout')
                ->icon('heroicon-o-banknotes')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Request Payout')
                ->modalDescription(fn (): string =>
                    'You are requesting a payout of your full approved balance: ₹' .
                    number_format($this->getApprovedBalance(), 2) .
                    '. The admin will process it shortly.'
                )
                ->modalSubmitActionLabel('Submit Request')
                ->visible(fn (): bool => ! $this->hasPendingRequest() && $this->getApprovedBalance() > 0)
                ->action(function (): void {
                    $vendor = auth()->user();
                    $balance = $this->getApprovedBalance();

                    // Double-check guards (race condition protection)
                    if (VendorPayoutRequest::where('user_id', $vendor->id)->where('status', 'pending')->exists()) {
                        Notification::make()
                            ->title('You already have a pending payout request.')
                            ->danger()
                            ->send();
                        return;
                    }

                    if ($balance <= 0) {
                        Notification::make()
                            ->title('Your current balance is ₹0. Nothing to request.')
                            ->danger()
                            ->send();
                        return;
                    }

                    VendorPayoutRequest::create([
                        'user_id'          => $vendor->id,
                        'amount_requested' => $balance,
                        'status'           => 'pending',
                    ]);

                    app(PortalTelegramAlertService::class)->notifyVendorPayoutRequested($vendor, $balance);

                    Notification::make()
                        ->title('Payout request of ₹' . number_format($balance, 0) . ' submitted.')
                        ->body('The admin will process it shortly.')
                        ->success()
                        ->send();
                }),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                VendorPayoutRequest::query()->where('user_id', auth()->id())
            )
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Requested')
                    ->dateTime('d M Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount_requested')
                    ->label('Amount (₹)')
                    ->formatStateUsing(fn ($state): string => '₹' . number_format((float) $state, 2)),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state): string => match ((string) $state) {
                        'pending'   => 'warning',
                        'fulfilled' => 'success',
                        'rejected'  => 'danger',
                        default     => 'gray',
                    })
                    ->formatStateUsing(fn ($state): string => ucfirst((string) $state)),

                Tables\Columns\TextColumn::make('fulfilled_at')
                    ->label('Fulfilled')
                    ->dateTime('d M Y')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('notes')
                    ->placeholder('—')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('id', 'desc')
            ->actions([])
            ->bulkActions([])
            ->emptyStateHeading('No payout requests yet')
            ->emptyStateDescription('Click "Request Payout" above when you have an approved balance.')
            ->paginated([10, 25]);
    }
}
