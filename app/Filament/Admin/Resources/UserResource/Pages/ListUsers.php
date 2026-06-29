<?php

namespace App\Filament\Admin\Resources\UserResource\Pages;

use App\Filament\Admin\Actions\InviteTelegramAction;
use App\Filament\Admin\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            InviteTelegramAction::make('invite-user')->role(null),
            Actions\CreateAction::make(),
        ];
    }
}
