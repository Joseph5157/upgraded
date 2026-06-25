<?php

namespace App\Filament\Client\Pages;

use App\Enums\OrderStatus;
use App\Models\Order;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class MyDownloads extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-down-tray';

    protected static ?string $navigationLabel = 'My Downloads';

    protected static ?int $navigationSort = 4;

    protected static ?string $title = 'My Downloads';

    protected static string $view = 'filament.client.pages.my-downloads';

    public function table(Table $table): Table
    {
        $client = auth()->user()?->client;

        $query = Order::query()
            ->where('client_id', $client?->id ?? 0)
            ->where('source', 'account')
            ->whereIn('status', [OrderStatus::Processing, OrderStatus::Delivered])
            ->with(['files', 'report']);

        return $table
            ->query($query)
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Order #')
                    ->sortable(),

                Tables\Columns\TextColumn::make('token_view')
                    ->label('Tracking ID')
                    ->formatStateUsing(fn ($state): string => '#' . $state)
                    ->searchable(),

                Tables\Columns\TextColumn::make('first_file_name')
                    ->label('File')
                    ->state(fn (Order $record): string =>
                        $record->files->first()?->original_name ?? '—'
                    ),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ucfirst(
                        $state instanceof OrderStatus ? $state->value : $state
                    ))
                    ->color(fn ($state): string => match (
                        $state instanceof OrderStatus ? $state->value : $state
                    ) {
                        'processing' => 'warning',
                        'delivered'  => 'success',
                        default      => 'gray',
                    }),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->actions([
                Action::make('download_plag')
                    ->label('Plag Report')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->visible(fn (Order $record): bool =>
                        $record->status === OrderStatus::Delivered
                        && $record->report !== null
                        && ! empty($record->report->plag_report_path)
                    )
                    ->action(fn (Order $record) => Storage::disk(
                        $record->report->plag_report_disk ?: 'local'
                    )->download(
                        $record->report->plag_report_path,
                        $record->report->plag_report_original_name ?: 'plag-report.pdf'
                    )),

                Action::make('download_ai')
                    ->label('AI Report')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('info')
                    ->visible(fn (Order $record): bool =>
                        $record->status === OrderStatus::Delivered
                        && $record->report !== null
                        && ! empty($record->report->ai_report_path)
                    )
                    ->action(fn (Order $record) => Storage::disk(
                        $record->report->ai_report_disk ?: 'local'
                    )->download(
                        $record->report->ai_report_path,
                        $record->report->ai_report_original_name ?: 'ai-report.pdf'
                    )),
            ])
            ->defaultSort('updated_at', 'desc')
            ->bulkActions([])
            ->emptyStateHeading('No downloads available')
            ->emptyStateDescription('Completed reports will appear here when ready.')
            ->paginated([10, 25, 50]);
    }
}
