<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\AnnouncementResource\Pages;
use App\Models\Announcement;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AnnouncementResource extends Resource
{
    protected static ?string $model = Announcement::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    protected static ?string $navigationLabel = 'Announcements';

    protected static ?string $navigationGroup = 'Content';

    protected static ?int $navigationSort = 20;

    protected static ?string $label = 'Announcement';

    protected static ?string $pluralLabel = 'Announcements';

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Announcement Details')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('message')
                            ->required()
                            ->maxLength(1000)
                            ->rows(3),

                        Forms\Components\Select::make('target')
                            ->options([
                                'all'    => 'All Users',
                                'vendor' => 'Vendors Only',
                                'client' => 'Clients Only',
                            ])
                            ->required()
                            ->default('all'),

                        Forms\Components\Select::make('type')
                            ->options([
                                'info'    => 'Info',
                                'warning' => 'Warning',
                                'success' => 'Success',
                                'danger'  => 'Danger',
                            ])
                            ->required()
                            ->default('info'),

                        Forms\Components\DateTimePicker::make('expires_at')
                            ->label('Expires At (optional)')
                            ->nullable()
                            ->after('now'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->sortable(),

                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->limit(40),

                Tables\Columns\TextColumn::make('target')
                    ->badge()
                    ->color(fn ($state): string => match ((string) $state) {
                        'all'    => 'primary',
                        'vendor' => 'success',
                        'client' => 'info',
                        default  => 'gray',
                    })
                    ->formatStateUsing(fn ($state): string => ucfirst((string) $state)),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn ($state): string => match ((string) $state) {
                        'info'    => 'info',
                        'warning' => 'warning',
                        'success' => 'success',
                        'danger'  => 'danger',
                        default   => 'gray',
                    })
                    ->formatStateUsing(fn ($state): string => ucfirst((string) $state)),

                Tables\Columns\IconColumn::make('active')
                    ->boolean(),

                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Created By')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Expires')
                    ->dateTime('d M Y H:i')
                    ->placeholder('Never')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('toggle')
                    ->label(fn (Announcement $record): string => $record->active ? 'Deactivate' : 'Activate')
                    ->icon(fn (Announcement $record): string => $record->active
                        ? 'heroicon-o-eye-slash'
                        : 'heroicon-o-eye')
                    ->color(fn (Announcement $record): string => $record->active ? 'warning' : 'success')
                    ->requiresConfirmation()
                    ->action(function (Announcement $record): void {
                        $record->update(['active' => ! $record->active]);

                        Notification::make()
                            ->success()
                            ->title('Announcement ' . ($record->active ? 'activated' : 'deactivated') . '.')
                            ->send();
                    }),

                Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc')
            ->bulkActions([])
            ->paginated([10, 25, 50]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListAnnouncements::route('/'),
            'create' => Pages\CreateAnnouncement::route('/create'),
        ];
    }
}
