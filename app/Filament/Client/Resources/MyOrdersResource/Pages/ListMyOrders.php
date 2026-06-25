<?php

namespace App\Filament\Client\Resources\MyOrdersResource\Pages;

use App\Filament\Client\Pages\UploadFiles;
use App\Filament\Client\Resources\MyOrdersResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListMyOrders extends ListRecords
{
    protected static string $resource = MyOrdersResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('upload')
                ->label('Upload New File')
                ->icon('heroicon-o-arrow-up-tray')
                ->url(UploadFiles::getUrl())
                ->color('primary'),
        ];
    }
}
