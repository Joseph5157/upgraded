<?php

namespace App\Filament\Finance\Resources\VendorPayoutResource\Pages;

use App\Filament\Finance\Resources\VendorPayoutResource;
use App\Models\User;
use App\Services\Finance\VendorPayoutService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateVendorPayout extends CreateRecord
{
    protected static string $resource = VendorPayoutResource::class;

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        $vendor = User::findOrFail($data['user_id']);

        try {
            return app(VendorPayoutService::class)->recordPayout($vendor, $data, auth()->user());
        } catch (\InvalidArgumentException|\RuntimeException $e) {
            Notification::make()->title('Payout failed')->body($e->getMessage())->danger()->send();
            $this->halt();
        }
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Vendor payout recorded.';
    }
}
