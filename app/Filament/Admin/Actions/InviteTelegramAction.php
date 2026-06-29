<?php

namespace App\Filament\Admin\Actions;

use App\Models\PendingInvite;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class InviteTelegramAction extends Action
{
    protected ?string $inviteRole = null;

    public function role(?string $role): static
    {
        $this->inviteRole = $role;

        return $this;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $role = $this->inviteRole;

        $this
            ->label($role
                ? 'Invite ' . ucfirst($role) . ' via Telegram'
                : 'Invite via Telegram'
            )
            ->icon('heroicon-o-paper-airplane')
            ->color('success')
            ->modalHeading($role
                ? 'Invite New ' . ucfirst($role)
                : 'Invite New User'
            )
            ->modalSubmitActionLabel('Create Invite')
            ->form(fn () => $this->buildFormSchema())
            ->action(fn (array $data) => $this->createInvite($data));
    }

    protected function buildFormSchema(): array
    {
        $schema = [];

        $schema[] = TextInput::make('name')
            ->label('Name')
            ->required()
            ->maxLength(255);

        if ($this->inviteRole === null) {
            $schema[] = Select::make('role')
                ->label('Role')
                ->options([
                    'admin'  => 'Admin',
                    'vendor' => 'Vendor',
                    'client' => 'Client',
                ])
                ->required()
                ->live();

            $schema[] = TextInput::make('price_per_file')
                ->label('Rate per File (₹)')
                ->numeric()
                ->minValue(0)
                ->step(0.01)
                ->visible(fn (Get $get) => $get('role') === 'client');

            $schema[] = TextInput::make('slots')
                ->label('Total Slots')
                ->numeric()
                ->minValue(1)
                ->visible(fn (Get $get) => $get('role') === 'client');

            $schema[] = TextInput::make('payout_rate')
                ->label('Payout Rate per File (₹)')
                ->numeric()
                ->minValue(0)
                ->step(0.01)
                ->visible(fn (Get $get) => $get('role') === 'vendor');
        } elseif ($this->inviteRole === 'client') {
            $schema[] = TextInput::make('price_per_file')
                ->label('Rate per File (₹)')
                ->numeric()
                ->minValue(0)
                ->step(0.01);

            $schema[] = TextInput::make('slots')
                ->label('Total Slots')
                ->numeric()
                ->minValue(1);
        } elseif ($this->inviteRole === 'vendor') {
            $schema[] = TextInput::make('payout_rate')
                ->label('Payout Rate per File (₹)')
                ->numeric()
                ->minValue(0)
                ->step(0.01);
        }

        return $schema;
    }

    protected function createInvite(array $data): void
    {
        $role = $this->inviteRole ?? $data['role'];

        $botUsername = config('telegram.bot_username');

        if (! $botUsername) {
            Notification::make()
                ->danger()
                ->title('Telegram bot username is not configured')
                ->body('Set TELEGRAM_BOT_USERNAME in your .env file.')
                ->send();

            Log::warning('telegram.invite_link.missing_bot_username', [
                'invite_name' => $data['name'],
                'invite_role' => $role,
            ]);

            return;
        }

        $token = Str::random(32);

        PendingInvite::create([
            'name'           => $data['name'],
            'role'           => $role,
            'slots'          => $data['slots'] ?? null,
            'price_per_file' => $data['price_per_file'] ?? null,
            'payout_rate'    => $data['payout_rate'] ?? null,
            'invite_token'   => $token,
            'expires_at'     => now()->addDays(7),
        ]);

        $link = "https://t.me/{$botUsername}?start=invite_{$token}";

        // Show a small success toast
        Notification::make()
            ->success()
            ->title('Invite created for ' . $data['name'])
            ->send();

        // Show a copyable dialog box with the link via JavaScript
        $jsLink = str_replace("'", "\\'", $link);
        $jsName = str_replace("'", "\\'", $data['name']);

        $this->getLivewire()->js(
            "navigator.clipboard.writeText('{$jsLink}')" .
            ".then(() => alert('Invite link for {$jsName} copied to clipboard!\\n\\n{$jsLink}'))" .
            ".catch(() => prompt('Copy this invite link for {$jsName}:', '{$jsLink}'))"
        );

        Log::info('telegram.invite_link.created', [
            'invite_name'       => $data['name'],
            'invite_role'       => $role,
            'invite_expires_at' => now()->addDays(7)->toIso8601String(),
        ]);
    }
}
