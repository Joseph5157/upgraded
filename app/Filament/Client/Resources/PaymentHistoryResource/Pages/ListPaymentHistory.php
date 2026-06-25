<?php

namespace App\Filament\Client\Resources\PaymentHistoryResource\Pages;

use App\Filament\Client\Resources\PaymentHistoryResource;
use Filament\Resources\Pages\ListRecords;

class ListPaymentHistory extends ListRecords
{
    protected static string $resource = PaymentHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
