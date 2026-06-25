<?php

namespace App\Filament\Finance\Resources\ClientPaymentResource\Pages;

use App\Filament\Finance\Resources\ClientPaymentResource;
use App\Models\Client;
use App\Services\Finance\ClientPaymentService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateClientPayment extends CreateRecord
{
    protected static string $resource = ClientPaymentResource::class;

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        $client = Client::findOrFail($data['client_id']);

        return app(ClientPaymentService::class)->record($client, $data, auth()->user());
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Payment recorded and credits added.';
    }
}
