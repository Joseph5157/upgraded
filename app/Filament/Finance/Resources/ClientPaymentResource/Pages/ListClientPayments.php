<?php

namespace App\Filament\Finance\Resources\ClientPaymentResource\Pages;

use App\Filament\Finance\Resources\ClientPaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListClientPayments extends ListRecords
{
    protected static string $resource = ClientPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
