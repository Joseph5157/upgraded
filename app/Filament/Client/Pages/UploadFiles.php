<?php

namespace App\Filament\Client\Pages;

use App\Filament\Client\Resources\MyOrdersResource;
use App\Services\CreateClientOrderService;
use App\Support\LogContext;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Log;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class UploadFiles extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationLabel = 'Upload Files';

    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.client.pages.upload-files';

    public ?array $data = [];

    /** Refreshed after each successful upload so the credit display stays live. */
    public int $creditBalance = 0;

    public function mount(): void
    {
        $client = auth()->user()?->client;
        $this->creditBalance = $client ? (int) $client->credit_balance : 0;
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Credit Summary')
                    ->description('Each file uploaded uses 1 credit.')
                    ->schema([
                        Placeholder::make('available_credits')
                            ->label('Available Credits')
                            ->content(fn (): string => $this->creditBalance . ' credit' . ($this->creditBalance === 1 ? '' : 's')),
                        Placeholder::make('cost_info')
                            ->label('Cost per file')
                            ->content('1 credit'),
                    ])
                    ->columns(2),

                Section::make('Upload')
                    ->schema([
                        FileUpload::make('file')
                            ->label('File (PDF, DOC, DOCX, or ZIP)')
                            ->required()
                            ->acceptedFileTypes([
                                'application/pdf',
                                'application/msword',
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                'application/zip',
                                'application/x-zip-compressed',
                            ])
                            ->maxSize(102400)
                            ->storeFiles(false),

                        Textarea::make('notes')
                            ->label('Notes (optional)')
                            ->rows(3)
                            ->maxLength(1000)
                            ->placeholder('Any additional instructions for the vendor...'),
                    ]),
            ])
            ->statePath('data');
    }

    public function upload(): void
    {
        $data = $this->form->getState();

        $user   = auth()->user();
        $client = $user?->client;

        if (! $client) {
            Notification::make()
                ->title('Account Error')
                ->body('Your account is not linked to a client profile. Please contact support.')
                ->danger()
                ->send();
            return;
        }

        $file = $this->resolveUploadedFile($data['file'] ?? null);

        if (! $file) {
            Notification::make()
                ->title('No File Selected')
                ->body('Please select a file to upload.')
                ->danger()
                ->send();
            return;
        }

        try {
            $order = app(CreateClientOrderService::class)->execute(
                $client,
                [$file],
                'account',
                [
                    'notes'              => ($data['notes'] ?? null) ?: null,
                    'created_by_user_id' => $user->id,
                ],
            );

            // Refresh credit balance display after deduction
            $this->creditBalance = (int) $client->fresh()->credit_balance;

            Notification::make()
                ->title('File Uploaded Successfully')
                ->body("Order #{$order->id} created. Tracking ID: {$order->token_view}")
                ->success()
                ->send();

            $this->redirect(MyOrdersResource::getUrl('index'));
        } catch (\Exception $e) {
            Log::warning('order.create_failed', array_merge(
                LogContext::forUser($user, LogContext::currentRequest()),
                [
                    'source'    => 'filament_client_upload',
                    'client_id' => $client->id,
                    'exception' => class_basename($e),
                    'message'   => $e->getMessage(),
                ]
            ));

            Notification::make()
                ->title('Upload Failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Resolve the Filament FileUpload value to a standard UploadedFile.
     *
     * With storeFiles(false), the value may be a TemporaryUploadedFile,
     * a Livewire tmp path string, or an array wrapping either.
     */
    private function resolveUploadedFile(mixed $value): ?\Illuminate\Http\UploadedFile
    {
        if ($value === null || $value === '' || $value === []) {
            return null;
        }

        if ($value instanceof \Illuminate\Http\UploadedFile) {
            return $value;
        }

        if (is_array($value)) {
            return $this->resolveUploadedFile(reset($value));
        }

        if (is_string($value)) {
            return TemporaryUploadedFile::createFromLivewire($value);
        }

        return null;
    }
}
