<?php

namespace App\Filament\Client\Pages;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\RefundRequest;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Cache;

class RefundRequests extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-uturn-left';

    protected static ?string $navigationLabel = 'Refund Requests';

    protected static ?int $navigationSort = 6;

    protected static ?string $title = 'Refund Requests';

    protected static string $view = 'filament.client.pages.refund-requests';

    public function table(Table $table): Table
    {
        $client = auth()->user()?->client;
        $clientId = $client?->id ?? 0;

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

                Tables\Columns\TextColumn::make('resolved_at')
                    ->label('Resolved')
                    ->dateTime('d M Y')
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                Tables\Actions\Action::make('request_refund')
                    ->label('Request Refund')
                    ->icon('heroicon-o-plus')
                    ->color('primary')
                    ->modalHeading('Request a Refund')
                    ->modalDescription('Select an order and provide a reason for your refund request.')
                    ->form([
                        Select::make('order_id')
                            ->label('Order')
                            ->options(function () {
                                $client = auth()->user()?->client;
                                if (! $client) {
                                    return [];
                                }

                                $refundableStatuses = [
                                    OrderStatus::Delivered,
                                    OrderStatus::Processing,
                                    OrderStatus::Claimed,
                                ];

                                // Exclude orders that already have a pending refund
                                $pendingOrderIds = RefundRequest::where('client_id', $client->id)
                                    ->where('status', 'pending')
                                    ->pluck('order_id');

                                return Order::where('client_id', $client->id)
                                    ->whereIn('status', $refundableStatuses)
                                    ->whereNotIn('id', $pendingOrderIds)
                                    ->orderByDesc('id')
                                    ->get()
                                    ->mapWithKeys(fn (Order $order) => [
                                        $order->id => "#{$order->id} — {$order->token_view} ({$order->status->value})",
                                    ]);
                            })
                            ->required()
                            ->searchable(),

                        Textarea::make('reason')
                            ->label('Reason')
                            ->required()
                            ->maxLength(1000)
                            ->rows(3),
                    ])
                    ->action(function (array $data): void {
                        $user = auth()->user();
                        $client = $user?->client;

                        if (! $client) {
                            Notification::make()
                                ->danger()
                                ->title('No client account linked.')
                                ->send();
                            return;
                        }

                        $order = Order::find($data['order_id']);

                        if (! $order || (int) $order->client_id !== (int) $client->id) {
                            Notification::make()
                                ->danger()
                                ->title('You can only request a refund for your own orders.')
                                ->send();
                            return;
                        }

                        $refundableStatuses = [
                            OrderStatus::Delivered,
                            OrderStatus::Processing,
                            OrderStatus::Claimed,
                        ];

                        if (! in_array($order->status, $refundableStatuses)) {
                            Notification::make()
                                ->danger()
                                ->title('This order is not eligible for a refund.')
                                ->send();
                            return;
                        }

                        if (RefundRequest::where('order_id', $order->id)->where('status', 'pending')->exists()) {
                            Notification::make()
                                ->danger()
                                ->title('A refund request for this order is already pending.')
                                ->send();
                            return;
                        }

                        RefundRequest::create([
                            'order_id'  => $order->id,
                            'client_id' => $client->id,
                            'user_id'   => $user->id,
                            'reason'    => $data['reason'],
                            'status'    => 'pending',
                        ]);

                        Cache::forget('admin_nav_pending_refunds');

                        Notification::make()
                            ->success()
                            ->title('Refund request submitted.')
                            ->body('The admin will review it shortly.')
                            ->send();
                    }),
            ])
            ->actions([])
            ->bulkActions([])
            ->emptyStateHeading('No refund requests')
            ->emptyStateDescription('You can request a refund for eligible orders using the button above.')
            ->paginated([10, 25]);
    }
}
