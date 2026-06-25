<?php

namespace App\Filament\Finance\Resources\BusinessExpenseResource\Pages;

use App\Filament\Finance\Resources\BusinessExpenseResource;
use App\Services\Finance\BusinessExpenseService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateBusinessExpense extends CreateRecord
{
    protected static string $resource = BusinessExpenseResource::class;

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        try {
            return app(BusinessExpenseService::class)->recordExpense($data, auth()->user());
        } catch (\InvalidArgumentException $e) {
            Notification::make()->title('Expense failed')->body($e->getMessage())->danger()->send();
            $this->halt();
        }
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Business expense recorded.';
    }
}
