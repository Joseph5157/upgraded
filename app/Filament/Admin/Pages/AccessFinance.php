<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Redirect;

class AccessFinance extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationLabel = 'Finance';
    protected static ?int $navigationSort = 100;

    public function mount()
    {
        return Redirect::to('/filament-finance');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->role === 'admin' ?? false;
    }
}
