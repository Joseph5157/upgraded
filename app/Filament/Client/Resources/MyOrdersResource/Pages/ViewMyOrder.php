<?php

namespace App\Filament\Client\Resources\MyOrdersResource\Pages;

use App\Filament\Client\Resources\MyOrdersResource;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Storage;

class ViewMyOrder extends ViewRecord
{
    protected static string $resource = MyOrdersResource::class;

    protected function getHeaderActions(): array
    {
        /** @var Order $record */
        $record = $this->record;

        $actions = [];

        if ($record->report?->plag_report_path) {
            $actions[] = Action::make('download_plag_report')
                ->label('Download Plagiarism Report')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(fn () => Storage::disk(
                    $record->report->plag_report_disk ?: 'local'
                )->download(
                    $record->report->plag_report_path,
                    $record->report->plag_report_original_name ?: 'report.pdf'
                ));
        }

        return $actions;
    }
}
