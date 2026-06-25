<?php

namespace App\Filament\Vendor\Resources\MyWorkResource\Pages;

use App\Enums\OrderStatus;
use App\Filament\Vendor\Resources\MyWorkResource;
use App\Services\OrderWorkflowService;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewMyWork extends ViewRecord
{
    protected static string $resource = MyWorkResource::class;

    protected function getHeaderActions(): array
    {
        $record = $this->record;

        $isActive = in_array(
            $record->status instanceof OrderStatus ? $record->status->value : $record->status,
            ['claimed', 'processing']
        );

        if (! $isActive) {
            return [];
        }

        return [
            Action::make('upload_report')
                ->label('Upload Report')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('warning')
                ->modalHeading('Upload Report')
                ->modalDescription("Order #{$record->id} — {$record->files_count} file(s)")
                ->modalSubmitActionLabel('Upload & Deliver')
                ->form(MyWorkResource::uploadReportFormSchema())
                ->action(fn (array $data) => MyWorkResource::handleReportUpload($data, $this->record)),

            Action::make('mark_failed')
                ->label('Mark Failed')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Mark Order as Failed')
                ->modalDescription("Order #{$record->id} — This will mark the order as failed and cannot be undone by you.")
                ->modalSubmitActionLabel('Confirm Failed')
                ->form([
                    TextInput::make('failure_reason')
                        ->label('Reason for failure')
                        ->required()
                        ->maxLength(500)
                        ->placeholder('e.g., File corrupted, unsupported format, tool failure'),
                ])
                ->action(function (array $data): void {
                    try {
                        app(OrderWorkflowService::class)->markFailed(
                            $this->record,
                            auth()->user(),
                            $data['failure_reason'],
                        );

                        Notification::make()
                            ->title('Order Marked as Failed')
                            ->body("Order #{$this->record->id} has been marked as failed.")
                            ->warning()
                            ->send();
                    } catch (\App\Exceptions\WorkflowException $e) {
                        Notification::make()
                            ->title('Cannot Mark Failed')
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
                }),
        ];
    }
}
