<?php

namespace App\Providers\Filament;

use App\Http\Middleware\FilamentPanelRole;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\HtmlString;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('admin')
            ->path('filament-admin')
            ->login()
            ->brandName('PlagExpert Admin')
            ->colors([
                'primary' => Color::Indigo,
                'gray'    => Color::Zinc,
                'danger'  => Color::Rose,
                'success' => Color::Emerald,
                'warning' => Color::Amber,
                'info'    => Color::Sky,
            ])
            ->darkMode(true)
            ->sidebarCollapsibleOnDesktop()
            ->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\\Filament\\Admin\\Resources')
            ->discoverPages(in: app_path('Filament/Admin/Pages'), for: 'App\\Filament\\Admin\\Pages')
            ->discoverWidgets(in: app_path('Filament/Admin/Widgets'), for: 'App\\Filament\\Admin\\Widgets')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                FilamentPanelRole::class . ':admin',
            ])
            ->authGuard('web')
            ->registerNavigationItems([
                NavigationItem::make('Finance')
                    ->url('/filament-finance')
                    ->icon('heroicon-o-currency-dollar')
                    ->isActiveWhen(fn (): bool => request()->is('filament-finance*')),
            ])
            ->renderHook(
                PanelsRenderHook::BODY_START,
                fn (): HtmlString => new HtmlString(<<<'HTML'
                <script>
                    (function () {
                        function closeMobileSidebar() {
                            if (window.innerWidth >= 1024) return;
                            document.querySelectorAll('[x-data]').forEach(function (el) {
                                var stacks = el._x_dataStack;
                                if (!stacks) return;
                                stacks.forEach(function (data) {
                                    if ('isNavigationOpen' in data) {
                                        data.isNavigationOpen = false;
                                    }
                                });
                            });
                        }
                        document.addEventListener('alpine:initialized', closeMobileSidebar);
                        document.addEventListener('livewire:navigated', closeMobileSidebar);
                    })();
                </script>
                HTML)
            );
    }
}
