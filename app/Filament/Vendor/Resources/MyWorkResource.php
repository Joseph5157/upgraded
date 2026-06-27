<?php

namespace App\Filament\Vendor\Resources;

use App\Enums\OrderStatus;
use App\Exceptions\VendorReportStorageException;
use App\Exceptions\WorkflowException;
use App\Filament\Vendor\Resources\MyWorkResource\Pages;
use App\Models\Order;
use App\Services\OrderWorkflowService;
use App\Services\UploadVendorReportService;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class MyWorkResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationLabel = 'My Work';

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';

    protected static ?int $navigationSort = 2;

    protected static ?string $label = 'Order';

    protected static ?string $pluralLabel = 'My Work';

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        if (! $user || $user->role !== 'vendor') {
            return parent::getEloquentQuery()->whereRaw('1 = 0');
        }

        return parent::getEloquentQuery()
            ->where('claimed_by', $user->id)
            ->with(['client']);
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
                    ->columns(2)
                    ->schema([
                        TextEntry::make('id')
                            ->label('Order #'),

                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->formatStateUsing(fn ($state): string => $state instanceof OrderStatus
                                ? ucfirst($state->value)
                                : ucfirst((string) $state)
                            )
                            ->color(fn ($state): string => match(
                                $state instanceof OrderStatus ? $state->value : (string) $state
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

                        TextEntry::make('due_at')
                            ->label('Due')
                            ->date('d M Y')
                            ->placeholder('—'),

                        TextEntry::make('claimed_at')
                            ->label('Claimed')
                            ->dateTime()
                            ->placeholder('—'),

                        TextEntry::make('delivered_at')
                            ->label('Delivered')
                            ->dateTime()
                            ->placeholder('—'),
                    ]),

                Section::make('Earnings')
                    ->visible(fn ($record): bool => $record && (float) $record->vendor_amount > 0)
                    ->schema([
                        TextEntry::make('vendor_amount')
                            ->label('Earning')
                            ->formatStateUsing(fn ($state): string => '₹' . number_format((float) $state, 2)),

                        TextEntry::make('vendor_rate_per_file')
                            ->label('Rate/File')
                            ->formatStateUsing(fn ($state): string => '₹' . number_format((float) $state, 2)),
                    ]),
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
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof OrderStatus
                        ? ucfirst($state->value)
                        : ucfirst((string) $state)
                    )
                    ->color(fn ($state): string => match(
                        $state instanceof OrderStatus ? $state->value : (string) $state
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

                Tables\Columns\TextColumn::make('due_at')
                    ->label('Due')
                    ->date('d M Y')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Assigned')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Action::make('upload_report')
                    ->label('Upload Report')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('warning')
                    ->visible(fn (Order $record): bool => $record->status instanceof OrderStatus
                        ? in_array($record->status->value, ['claimed', 'processing'])
                        : in_array($record->status, ['claimed', 'processing'])
                    )
                    ->modalHeading('Upload Report')
                    ->modalDescription(fn (Order $record): string => "Order #{$record->id} — {$record->files_count} file(s)")
                    ->modalSubmitActionLabel('Upload & Deliver')
                    ->form(static::uploadReportFormSchema())
                    ->action(fn (array $data, Order $record) => static::handleReportUpload($data, $record)),

                Action::make('mark_failed')
                    ->label('Mark Failed')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Order $record): bool => $record->status instanceof OrderStatus
                        ? in_array($record->status->value, ['claimed', 'processing'])
                        : in_array($record->status, ['claimed', 'processing'])
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Mark Order as Failed')
                    ->modalDescription(fn (Order $record): string => "Order #{$record->id} — This will mark the order as failed and cannot be undone by you.")
                    ->modalSubmitActionLabel('Confirm Failed')
                    ->form([
                        TextInput::make('failure_reason')
                            ->label('Reason for failure')
                            ->required()
                            ->maxLength(500)
                            ->placeholder('e.g., File corrupted, unsupported format, tool failure'),
                    ])
                    ->action(function (array $data, Order $record): void {
                        try {
                            app(OrderWorkflowService::class)->markFailed(
                                $record,
                                auth()->user(),
                                $data['failure_reason'],
                            );

                            Notification::make()
                                ->title('Order Marked as Failed')
                                ->body("Order #{$record->id} has been marked as failed.")
                                ->warning()
                                ->send();
                        } catch (\App\Exceptions\WorkflowException $e) {
                            Notification::make()
                                ->title('Cannot Mark Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        } catch (\Throwable $e) {
                            report($e);

                            Notification::make()
                                ->title('Unexpected Error')
                                ->body('Something went wrong. Please try again.')
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->defaultSort('id', 'desc')
            ->defaultPaginationPageOption(10)
            ->paginationPageOptions([10, 25, 50])
            ->bulkActions([]);
    }

    // ─── Upload Report Modal ───────────────────────────────────────────

    public static function uploadReportFormSchema(): array
    {
        return [
            FileUpload::make('plag_report')
                ->label('Plagiarism Report (PDF)')
                ->required()
                ->acceptedFileTypes(['application/pdf'])
                ->maxSize(102400)
                ->storeFiles(false),

            FileUpload::make('ai_report')
                ->label('AI Detection Report (PDF)')
                ->acceptedFileTypes(['application/pdf'])
                ->maxSize(102400)
                ->storeFiles(false)
                ->hidden(fn (Get $get): bool => (bool) $get('ai_skipped')),

            Toggle::make('ai_skipped')
                ->label('AI report could not be generated')
                ->live()
                ->default(false),

            TextInput::make('ai_skip_reason')
                ->label('Reason for skipping AI report')
                ->maxLength(255)
                ->hidden(fn (Get $get): bool => ! (bool) $get('ai_skipped'))
                ->required(fn (Get $get): bool => (bool) $get('ai_skipped')),
        ];
    }

    public static function handleReportUpload(array $data, Order $record): void
    {
        $user = auth()->user();

        // Server-side authorization — must match existing service rules
        if ((int) $record->claimed_by !== (int) $user->id && $user->role !== 'admin') {
            Notification::make()
                ->title('Unauthorized')
                ->body('You are not authorized to upload a report for this order.')
                ->danger()
                ->send();

            return;
        }

        $plagReport = static::resolveUploadedFile($data['plag_report'] ?? null);
        $aiSkipped = ! empty($data['ai_skipped']);
        $aiSkipReason = $aiSkipped ? ($data['ai_skip_reason'] ?? null) : null;

        // When AI is skipped, ignore any stale file left in form state.
        // Matches UploadVendorReportRequest which rejects file + skip together.
        $aiReport = $aiSkipped ? null : static::resolveUploadedFile($data['ai_report'] ?? null);

        if (! $plagReport) {
            Notification::make()
                ->title('Validation Error')
                ->body('Plagiarism report is required.')
                ->danger()
                ->send();

            return;
        }

        if (! $aiReport && ! $aiSkipReason) {
            Notification::make()
                ->title('Validation Error')
                ->body('Please upload the AI report or provide a reason for skipping it.')
                ->danger()
                ->send();

            return;
        }

        try {
            app(UploadVendorReportService::class)->execute(
                $record,
                $user,
                $aiReport,
                $plagReport,
                $aiSkipReason,
            );

            Notification::make()
                ->title('Report Uploaded')
                ->body("Order #{$record->id} has been delivered successfully.")
                ->success()
                ->send();
        } catch (VendorReportStorageException $e) {
            Notification::make()
                ->title('Upload Failed')
                ->body('Failed to save files to storage. Please try again.')
                ->danger()
                ->send();
        } catch (WorkflowException $e) {
            Notification::make()
                ->title('Upload Failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        } catch (\Throwable $e) {
            report($e);

            Notification::make()
                ->title('Unexpected Error')
                ->body('Something went wrong. Please try again.')
                ->danger()
                ->send();
        }
    }

    /**
     * Convert Filament FileUpload data to an UploadedFile.
     *
     * With storeFiles(false), the value may be a TemporaryUploadedFile,
     * a Livewire tmp path string, or an array wrapping either of those.
     */
    private static function resolveUploadedFile(mixed $value): ?\Illuminate\Http\UploadedFile
    {
        if ($value === null || $value === '' || $value === []) {
            return null;
        }

        if ($value instanceof \Illuminate\Http\UploadedFile) {
            return $value;
        }

        if (is_array($value)) {
            return static::resolveUploadedFile(reset($value));
        }

        if (is_string($value)) {
            return TemporaryUploadedFile::createFromLivewire($value);
        }

        return null;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMyWork::route('/'),
            'view'  => Pages\ViewMyWork::route('/{record}'),
        ];
    }
}
