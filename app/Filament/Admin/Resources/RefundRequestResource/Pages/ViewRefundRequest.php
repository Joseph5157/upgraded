<?php

namespace App\Filament\Admin\Resources\RefundRequestResource\Pages;

use App\Filament\Admin\Resources\RefundRequestResource;
use App\Models\Client;
use App\Models\Order;
use App\Models\RefundRequest;
use App\Services\Finance\ClientCreditService;
use Filament\Actions;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ViewRefundRequest extends ViewRecord
{
    protected static string $resource = RefundRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('approve')
                ->label('Approve')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn (RefundRequest $record): bool => $record->status === 'pending')
                ->requiresConfirmation()
                ->modalHeading('Approve Refund Request')
                ->modalDescription('This will restore credits to the client if the order was debited under the new credit ledger system.')
                ->form([
                    Textarea::make('admin_note')
                        ->label('Admin Note (optional)')
                        ->maxLength(500)
                        ->rows(3),
                ])
                ->action(function (RefundRequest $record, array $data): void {
                    if ($record->status !== 'pending') {
                        Notification::make()
                            ->danger()
                            ->title('This refund request has already been resolved.')
                            ->send();
                        return;
                    }

                    $creditService = app(ClientCreditService::class);
                    $creditsRestored = false;

                    DB::transaction(function () use ($record, $data, $creditService, &$creditsRestored) {
                        $client = Client::where('id', $record->client_id)->lockForUpdate()->firstOrFail();
                        $order  = Order::where('id', $record->order_id)->lockForUpdate()->firstOrFail();

                        $creditsRestored = $creditService->refundOrderIfDebited(
                            $client,
                            $order,
                            Auth::user(),
                            'Admin approved refund request #' . $record->id . '.',
                        );

                        if ($client->status === 'suspended' && $client->fresh()->credit_balance > 0) {
                            $client->update(['status' => 'active']);
                        }

                        $record->update([
                            'status'      => 'approved',
                            'admin_note'  => $data['admin_note'] ?? null,
                            'resolved_at' => now(),
                        ]);
                    });

                    Cache::forget('admin_nav_pending_refunds');

                    $message = $creditsRestored
                        ? 'Refund approved. Credits have been restored to the client.'
                        : 'Refund approved. No credit refund was created because this order did not consume credits from the new ledger.';

                    Notification::make()
                        ->success()
                        ->title($message)
                        ->send();

                    $this->refreshFormData(['status', 'resolved_at', 'admin_note']);
                }),

            Actions\Action::make('reject')
                ->label('Reject')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn (RefundRequest $record): bool => $record->status === 'pending')
                ->form([
                    Textarea::make('admin_note')
                        ->label('Rejection Note')
                        ->maxLength(500)
                        ->rows(3),
                ])
                ->action(function (RefundRequest $record, array $data): void {
                    if ($record->status !== 'pending') {
                        Notification::make()
                            ->danger()
                            ->title('This refund request has already been resolved.')
                            ->send();
                        return;
                    }

                    $record->update([
                        'status'      => 'rejected',
                        'admin_note'  => $data['admin_note'] ?? null,
                        'resolved_at' => now(),
                    ]);

                    Cache::forget('admin_nav_pending_refunds');

                    Notification::make()
                        ->success()
                        ->title('Refund request rejected.')
                        ->send();

                    $this->refreshFormData(['status', 'resolved_at', 'admin_note']);
                }),
        ];
    }
}
