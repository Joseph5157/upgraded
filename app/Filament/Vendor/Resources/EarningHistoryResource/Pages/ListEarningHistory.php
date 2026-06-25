<?php

namespace App\Filament\Vendor\Resources\EarningHistoryResource\Pages;

use App\Filament\Vendor\Resources\EarningHistoryResource;
use Filament\Resources\Pages\ListRecords;

class ListEarningHistory extends ListRecords
{
    protected static string $resource = EarningHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
