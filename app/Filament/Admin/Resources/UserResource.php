<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Users & Clients';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('User Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->maxLength(20),
                        Forms\Components\Select::make('role')
                            ->options([
                                'admin' => 'Admin',
                                'staff' => 'Staff',
                                'accountant' => 'Accountant',
                                'vendor' => 'Vendor',
                                'client' => 'Client',
                            ])
                            ->required(),
                        Forms\Components\Toggle::make('is_super_admin')
                            ->label('Super Admin')
                            ->visible(fn () => auth()->user()?->isSuperAdmin()),
                        Forms\Components\Select::make('status')
                            ->options([
                                'active' => 'Active',
                                'frozen' => 'Frozen',
                            ])
                            ->required()
                            ->default('active'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Password')
                    ->schema([
                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->maxLength(255)
                            ->label(fn (string $operation) => $operation === 'create' ? 'Password' : 'New Password (leave blank to keep current)'),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('portal_number')
                    ->label('Portal #')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('telegram_chat_id')
                    ->label('Telegram ID')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('role')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'admin' => 'danger',
                        'staff' => 'warning',
                        'accountant' => 'info',
                        'vendor' => 'success',
                        'client' => 'primary',
                        default => 'gray',
                    }),
                Tables\Columns\IconColumn::make('is_super_admin')
                    ->label('Super')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'frozen' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->options([
                        'admin' => 'Admin',
                        'staff' => 'Staff',
                        'accountant' => 'Accountant',
                        'vendor' => 'Vendor',
                        'client' => 'Client',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'frozen' => 'Frozen',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('freeze')
                    ->label('Freeze')
                    ->icon('heroicon-o-lock-closed')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalDescription('This will freeze the user and prevent them from logging in.')
                    ->visible(fn (User $record): bool => $record->status === 'active'
                        && $record->id !== auth()->id()
                        && ! $record->isSuperAdmin())
                    ->action(fn (User $record) => $record->update([
                        'status' => 'frozen',
                        'frozen_at' => now(),
                    ])),
                Tables\Actions\Action::make('activate')
                    ->label('Activate')
                    ->icon('heroicon-o-lock-open')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (User $record): bool => $record->status === 'frozen')
                    ->action(fn (User $record) => $record->update([
                        'status' => 'active',
                        'frozen_at' => null,
                        'frozen_reason' => null,
                    ])),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes();
    }

    public static function canEdit($record): bool
    {
        $currentUser = auth()->user();

        // Only super_admin can edit another super_admin
        if ($record->isSuperAdmin() && ! $currentUser?->isSuperAdmin()) {
            return false;
        }

        return true;
    }

    public static function canDelete($record): bool
    {
        // Prevent deleting super admins
        if ($record->isSuperAdmin()) {
            return false;
        }

        // Prevent self-deletion
        return $record->id !== auth()->id();
    }
}
