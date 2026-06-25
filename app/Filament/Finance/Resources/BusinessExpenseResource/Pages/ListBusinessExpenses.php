<?php

namespace App\Filament\Finance\Resources\BusinessExpenseResource\Pages;

use App\Filament\Finance\Resources\BusinessExpenseResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBusinessExpenses extends ListRecords
{
    protected static string $resource = BusinessExpenseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
