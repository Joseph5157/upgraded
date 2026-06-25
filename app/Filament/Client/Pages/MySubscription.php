<?php

namespace App\Filament\Client\Pages;

use App\Models\ClientCreditTransaction;
use App\Models\ClientPayment;
use App\Models\RefundRequest;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class MySubscription extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationLabel = 'My Subscription';

    protected static ?int $navigationSort = 5;

    protected static ?string $title = 'My Subscription';

    protected static string $view = 'filament.client.pages.my-subscription';

    public string $activeTab = 'payments';

    public function getCreditsRemaining(): int
    {
        $client = auth()->user()?->client;

        return max(0, (int) ($client?->credit_balance ?? 0));
    }

    public function getCreditsUsed(): int
    {
        $client = auth()->user()?->client;
        if (! $client) {
            return 0;
        }

        return (int) abs(
            $client->creditTransactions()
                ->where('type', 'order_debit')
                ->sum('credits_delta')
        );
    }

    public function getRatePerFile(): string
    {
        $client = auth()->user()?->client;

        return '₹' . number_format((float) ($client?->price_per_file ?? 0), 2);
    }

    public function getPlanStatus(): string
    {
        $client = auth()->user()?->client;
        if (! $client) {
            return 'Unknown';
        }

        if ($client->plan_expiry && $client->plan_expiry->isPast()) {
            return 'Expired';
        }

        return 'Active';
    }

    public function getPlanExpiry(): ?string
    {
        $client = auth()->user()?->client;
        if (! $client?->plan_expiry) {
            return null;
        }

        return $client->plan_expiry->format('d M Y');
    }

    public function getLastPayment(): ?array
    {
        $client = auth()->user()?->client;
        if (! $client) {
            return null;
        }

        $payment = $client->clientPayments()
            ->where('status', ClientPayment::STATUS_CONFIRMED)
            ->orderByDesc('received_at')
            ->first();

        if (! $payment) {
            return null;
        }

        return [
            'credits' => $payment->credits_added,
            'date'    => $payment->received_at->format('d M Y'),
        ];
    }

    public function table(Table $table): Table
    {
        $client = auth()->user()?->client;
        $clientId = $client?->id ?? 0;

        if ($this->activeTab === 'refunds') {
            return $this->refundsTable($table, $clientId);
        }

        return $this->paymentsTable($table, $clientId);
    }

    protected function paymentsTable(Table $table, int $clientId): Table
    {
        return $table
            ->query(
                ClientPayment::query()->where('client_id', $clientId)
            )
            ->columns([
                Tables\Columns\TextColumn::make('received_at')
                    ->label('Date')
                    ->dateTime('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount_received')
                    ->label('Amount (₹)')
                    ->formatStateUsing(fn ($state): string => '₹' . number_format((float) $state, 2)),

                Tables\Columns\TextColumn::make('credits_added')
                    ->label('Credits Added')
                    ->numeric(),

                Tables\Columns\TextColumn::make('payment_mode')
                    ->label('Mode')
                    ->badge()
                    ->color(fn ($state): string => match ((string) $state) {
                        'upi'           => 'success',
                        'bank_transfer' => 'info',
                        'razorpay'      => 'primary',
                        'cash'          => 'warning',
                        default         => 'gray',
                    })
                    ->formatStateUsing(fn ($state): string => ucwords(str_replace('_', ' ', (string) $state))),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state): string => match ((string) $state) {
                        'confirmed' => 'success',
                        'voided'    => 'danger',
                        'refunded'  => 'warning',
                        default     => 'gray',
                    })
                    ->formatStateUsing(fn ($state): string => ucfirst((string) $state)),

                Tables\Columns\TextColumn::make('notes')
                    ->placeholder('—')
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('received_at', 'desc')
            ->actions([])
            ->bulkActions([])
            ->emptyStateHeading('No payments yet')
            ->paginated([10, 25]);
    }

    protected function refundsTable(Table $table, int $clientId): Table
    {
        return $table
            ->query(
                RefundRequest::query()->where('client_id', $clientId)
            )
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('order_id')
                    ->label('Order #'),

                Tables\Columns\TextColumn::make('reason')
                    ->limit(50),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state): string => match ((string) $state) {
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'pending'  => 'warning',
                        default    => 'gray',
                    })
                    ->formatStateUsing(fn ($state): string => ucfirst((string) $state)),

                Tables\Columns\TextColumn::make('admin_note')
                    ->label('Admin Note')
                    ->placeholder('—')
                    ->limit(40),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([])
            ->bulkActions([])
            ->emptyStateHeading('No refund requests')
            ->paginated([10, 25]);
    }
}
