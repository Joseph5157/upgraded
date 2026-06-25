<?php

namespace App\Filament\Client\Resources\CreditWalletResource\Pages;

use App\Filament\Client\Resources\CreditWalletResource;
use Filament\Resources\Pages\ListRecords;

class ListCreditTransactions extends ListRecords
{
    protected static string $resource = CreditWalletResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
