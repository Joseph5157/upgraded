<?php

namespace App\Filament\Admin\Resources\TopupRequestResource\Pages;

use App\Filament\Admin\Resources\TopupRequestResource;
use App\Models\Client;
use App\Models\TopupRequest;
use App\Services\PortalTelegramAlertService;
use Filament\Actions;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ViewTopupRequest extends ViewRecord
{
    protected static string $resource = TopupRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('approve')
                ->label('Approve')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn (TopupRequest $record): bool => $record->status === 'pending')
                ->requiresConfirmation()
                ->modalHeading('Approve Topup Request')
                ->modalDescription(fn (TopupRequest $record): string =>
                    "This will add {$record->amount_requested} slots to {$record->client->name}. " .
                    "Note: This modifies the legacy slots column, not credit_balance. " .
                    "For adding credits, use the Client Payments system instead."
                )
                ->action(function (TopupRequest $record): void {
                    if ($record->status !== 'pending') {
                        Notification::make()
                            ->danger()
                            ->title('This request has already been processed.')
                            ->send();
                        return;
                    }

                    DB::transaction(function () use ($record) {
                        $client = $record->client;
                        $newSlots = $client->slots + $record->amount_requested;

                        $client->update([
                            'slots'  => $newSlots,
                            'status' => ($client->status === 'suspended' && $client->slots_consumed < $newSlots)
                                ? 'active'
                                : $client->status,
                        ]);

                        $record->update([
                            'status'      => 'approved',
                            'reviewed_at' => now(),
                        ]);
                    });

                    Cache::forget('admin_nav_pending_topups');

                    try {
                        app(PortalTelegramAlertService::class)->notifyTopupApproved($record);
                    } catch (\Throwable $e) {
                        // Notification failure must not break the transaction
                    }

                    Notification::make()
                        ->success()
                        ->title("Approved! Added {$record->amount_requested} slots to {$record->client->name}.")
                        ->send();

                    $this->refreshFormData(['status', 'reviewed_at']);
                }),

            Actions\Action::make('reject')
                ->label('Reject')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn (TopupRequest $record): bool => $record->status === 'pending')
                ->form([
                    Textarea::make('notes')
                        ->label('Rejection Note')
                        ->maxLength(500)
                        ->rows(3),
                ])
                ->action(function (TopupRequest $record, array $data): void {
                    if ($record->status !== 'pending') {
                        Notification::make()
                            ->danger()
                            ->title('This request has already been processed.')
                            ->send();
                        return;
                    }

                    $record->update([
                        'status'      => 'rejected',
                        'notes'       => $data['notes'] ?? null,
                        'reviewed_at' => now(),
                    ]);

                    Cache::forget('admin_nav_pending_topups');

                    Notification::make()
                        ->success()
                        ->title("Rejected the top-up request from {$record->client->name}.")
                        ->send();

                    $this->refreshFormData(['status', 'reviewed_at', 'notes']);
                }),
        ];
    }
}
