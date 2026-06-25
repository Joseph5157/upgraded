<?php

namespace App\Filament\Finance\Resources\VendorPayoutResource\Pages;

use App\Filament\Finance\Resources\VendorPayoutResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVendorPayouts extends ListRecords
{
    protected static string $resource = VendorPayoutResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
