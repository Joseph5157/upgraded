<?php

namespace App\Filament\Admin\Resources\OrderResource\Pages;

use App\Enums\OrderStatus;
use App\Exceptions\WorkflowException;
use App\Filament\Admin\Resources\OrderResource;
use App\Services\OrderWorkflowService;
use Filament\Actions;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        $record = $this->record;

        $actions = [
            Actions\EditAction::make(),
        ];

        if ($record->status === OrderStatus::Failed) {
            $actions[] = Actions\Action::make('requeue')
                ->label('Requeue Order')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Requeue Failed Order')
                ->modalDescription("Order #{$record->id} will be returned to the pending pool. Failure history is preserved.")
                ->modalSubmitActionLabel('Requeue')
                ->form([
                    Textarea::make('requeue_reason')
                        ->label('Reason for requeue (optional)')
                        ->rows(2)
                        ->maxLength(500)
                        ->placeholder('e.g., Vendor error resolved, reassigning to queue'),
                ])
                ->action(function (array $data): void {
                    try {
                        app(OrderWorkflowService::class)->requeueFailed(
                            $this->record,
                            auth()->user(),
                            $data['requeue_reason'] ?: null,
                        );

                        Notification::make()
                            ->title('Order Requeued')
                            ->body("Order #{$this->record->id} is now pending and available for vendors.")
                            ->success()
                            ->send();

                        $this->refreshFormData(['status', 'claimed_by']);
                    } catch (WorkflowException $e) {
                        Notification::make()
                            ->title('Cannot Requeue')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    } catch (\Throwable $e) {
                        report($e);

                        Notification::make()
                            ->title('Unexpected Error')
                            ->body('Something went wrong. Please try again.')
                            ->danger()
                            ->send();
                    }
                });
        }

        return $actions;
    }
}
