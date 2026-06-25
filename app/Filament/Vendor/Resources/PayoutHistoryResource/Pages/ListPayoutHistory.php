<?php

namespace App\Filament\Vendor\Resources\PayoutHistoryResource\Pages;

use App\Filament\Vendor\Resources\PayoutHistoryResource;
use Filament\Resources\Pages\ListRecords;

class ListPayoutHistory extends ListRecords
{
    protected static string $resource = PayoutHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
