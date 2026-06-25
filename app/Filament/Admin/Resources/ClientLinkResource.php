<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ClientLinkResource\Pages;
use App\Models\Client;
use App\Models\ClientLink;
use App\Services\AuditLogger;
use Filament\Forms\Components\Select;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ClientLinkResource extends Resource
{
    protected static ?string $model = ClientLink::class;

    protected static ?string $navigationIcon = 'heroicon-o-link';

    protected static ?string $navigationLabel = 'Client Links';

    protected static ?string $navigationGroup = 'Content';

    protected static ?int $navigationSort = 21;

    protected static ?string $label = 'Client Link';

    protected static ?string $pluralLabel = 'Client Links';

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

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Link Details')
                    ->schema([
                        TextEntry::make('id')
                            ->label('Link #'),
                        TextEntry::make('client.name')
                            ->label('Client'),
                        TextEntry::make('token')
                            ->label('Token')
                            ->copyable(),
                        TextEntry::make('is_active')
                            ->label('Active')
                            ->badge()
                            ->formatStateUsing(fn ($state): string => $state ? 'Active' : 'Inactive')
                            ->color(fn ($state): string => $state ? 'success' : 'danger'),
                        TextEntry::make('created_at')
                            ->label('Created')
                            ->dateTime(),
                        TextEntry::make('expires_at')
                            ->label('Expires')
                            ->dateTime()
                            ->placeholder('—'),
                        TextEntry::make('revoked_at')
                            ->label('Revoked')
                            ->dateTime()
                            ->placeholder('—'),
                        TextEntry::make('orders_count')
                            ->label('Orders')
                            ->state(fn (ClientLink $record): int => $record->orders()->count()),
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

                Tables\Columns\TextColumn::make('client.name')
                    ->label('Client')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('token')
                    ->label('Token')
                    ->limit(12)
                    ->copyable()
                    ->tooltip(fn (ClientLink $record): string => $record->token),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                Tables\Columns\TextColumn::make('orders_count')
                    ->label('Orders')
                    ->counts('orders'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Expires')
                    ->dateTime('d M Y H:i')
                    ->placeholder('—')
                    ->sortable(),

                Tables\Columns\TextColumn::make('revoked_at')
                    ->label('Revoked')
                    ->dateTime('d M Y')
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        '1' => 'Active',
                        '0' => 'Revoked',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\Action::make('create_link')
                    ->label('Create Link')
                    ->icon('heroicon-o-plus')
                    ->color('primary')
                    ->form([
                        Select::make('client_id')
                            ->label('Client')
                            ->options(
                                Client::where('status', '!=', 'deleted')
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                            )
                            ->required()
                            ->searchable(),
                    ])
                    ->action(function (array $data): void {
                        $client = Client::with('links')->findOrFail($data['client_id']);

                        $hasUsableLink = $client->links()->usable()->exists();
                        if ($hasUsableLink) {
                            Notification::make()
                                ->danger()
                                ->title('This client already has an active guest link.')
                                ->body('Revoke the existing link before creating another one.')
                                ->send();
                            return;
                        }

                        $link = ClientLink::create([
                            'client_id'          => $client->id,
                            'token'              => Str::random(40),
                            'is_active'          => true,
                            'created_by_user_id' => auth()->id(),
                            'expires_at'         => now()->addDay(),
                        ]);

                        app(AuditLogger::class)->record('client_link.created', $link, [
                            'client_id'          => $client->id,
                            'created_by_user_id' => auth()->id(),
                            'expires_at'         => $link->expires_at?->toIso8601String(),
                        ]);

                        Notification::make()
                            ->success()
                            ->title('Upload link created for ' . $client->name . '.')
                            ->send();
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\Action::make('revoke')
                    ->label('Revoke')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (ClientLink $record): bool => ! $record->isRevoked())
                    ->action(function (ClientLink $record): void {
                        $record->update([
                            'is_active'          => false,
                            'revoked_at'         => now(),
                            'revoked_by_user_id' => auth()->id(),
                        ]);

                        app(AuditLogger::class)->record('client_link.revoked', $record, [
                            'client_id'          => $record->client_id,
                            'revoked_by_user_id' => auth()->id(),
                        ]);

                        Notification::make()
                            ->success()
                            ->title('Link revoked successfully.')
                            ->send();
                    }),

                Tables\Actions\Action::make('copy_url')
                    ->label('Copy URL')
                    ->icon('heroicon-o-clipboard')
                    ->color('gray')
                    ->visible(fn (ClientLink $record): bool => ! $record->isRevoked())
                    ->action(function (ClientLink $record): void {
                        // The URL is copied via the copyable token column.
                        // This action provides a full URL for convenience.
                        Notification::make()
                            ->info()
                            ->title('Link URL')
                            ->body(url("/u/{$record->token}"))
                            ->send();
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->bulkActions([])
            ->paginated([10, 25, 50]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClientLinks::route('/'),
            'view'  => Pages\ViewClientLink::route('/{record}'),
        ];
    }
}
