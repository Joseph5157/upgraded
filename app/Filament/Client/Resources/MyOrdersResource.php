<?php

namespace App\Filament\Client\Resources;

use App\Enums\OrderStatus;
use App\Filament\Client\Resources\MyOrdersResource\Pages;
use App\Models\Order;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class MyOrdersResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationLabel = 'My Orders';

    protected static ?string $navigationIcon = 'heroicon-o-document-duplicate';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationGroup = null;

    protected static ?string $label = 'Order';

    protected static ?string $pluralLabel = 'My Orders';

    public static function getEloquentQuery(): Builder
    {
        $client = auth()->user()?->client;

        if (! $client) {
            return parent::getEloquentQuery()->whereRaw('1 = 0');
        }

        return parent::getEloquentQuery()
            ->where('client_id', $client->id)
            ->with(['files', 'report']);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Order Details')
                    ->schema([
                        TextEntry::make('id')
                            ->label('Order #'),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->formatStateUsing(fn ($state) => ucfirst(
                                $state instanceof OrderStatus ? $state->value : $state
                            ))
                            ->color(fn ($state): string => match (
                                $state instanceof OrderStatus ? $state->value : $state
                            ) {
                                'pending'    => 'gray',
                                'claimed'    => 'info',
                                'processing' => 'warning',
                                'delivered'  => 'success',
                                'cancelled'  => 'danger',
                                'failed'     => 'danger',
                                default      => 'gray',
                            }),
                        TextEntry::make('files_count')
                            ->label('Files'),
                        TextEntry::make('source')
                            ->label('Source'),
                        TextEntry::make('created_at')
                            ->label('Submitted')
                            ->dateTime(),
                    ])
                    ->columns(2),

                Section::make('Report')
                    ->schema([
                        TextEntry::make('report_status')
                            ->label('Plagiarism Report')
                            ->state(fn ($record) => $record->report?->plag_report_path
                                ? 'Plagiarism report available'
                                : 'No report yet'
                            ),
                    ])
                    ->visible(fn ($record) => $record->report !== null),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Order #')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ucfirst(
                        $state instanceof OrderStatus ? $state->value : $state
                    ))
                    ->color(fn ($state): string => match (
                        $state instanceof OrderStatus ? $state->value : $state
                    ) {
                        'pending'    => 'gray',
                        'claimed'    => 'info',
                        'processing' => 'warning',
                        'delivered'  => 'success',
                        'cancelled'  => 'danger',
                        'failed'     => 'danger',
                        default      => 'gray',
                    }),
                Tables\Columns\TextColumn::make('files_count')
                    ->label('Files')
                    ->numeric(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Submitted')
                    ->dateTime('d M Y')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Action::make('download_report')
                    ->label('Download Report')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->visible(fn (Order $record): bool =>
                        ($record->status instanceof OrderStatus
                            ? $record->status === OrderStatus::Delivered
                            : $record->status === 'delivered'
                        )
                        && $record->report !== null
                        && ! empty($record->report->plag_report_path)
                    )
                    ->action(fn (Order $record) => Storage::disk(
                        $record->report->plag_report_disk ?: 'local'
                    )->download(
                        $record->report->plag_report_path,
                        $record->report->plag_report_original_name ?: 'report.pdf'
                    )),

                Action::make('delete_order')
                    ->label('Delete')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Delete Order')
                    ->modalDescription('This will permanently delete this order and its files. If credits were charged, they will be refunded.')
                    ->visible(fn (Order $record): bool =>
                        $record->status === OrderStatus::Pending
                        && $record->claimed_by === null
                    )
                    ->action(function (Order $record): void {
                        $client = auth()->user()?->client;

                        if (! $client) {
                            Notification::make()
                                ->danger()
                                ->title('Unable to delete order.')
                                ->send();
                            return;
                        }

                        try {
                            $creditsRefunded = app(\App\Services\DeleteClientOrderService::class)
                                ->execute($record, $client);

                            Notification::make()
                                ->success()
                                ->title('Order #' . $record->id . ' deleted.')
                                ->body($creditsRefunded ? 'Credits have been refunded.' : null)
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title('Cannot delete this order.')
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),
            ])
            ->defaultSort('id', 'desc')
            ->bulkActions([])
            ->paginated([10, 25, 50]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMyOrders::route('/'),
            'view'  => Pages\ViewMyOrder::route('/{record}'),
        ];
    }
}
