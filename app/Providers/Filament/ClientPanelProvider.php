<?php

namespace App\Providers\Filament;

use App\Http\Middleware\FilamentPanelRole;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
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

class ClientPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('client')
            ->path('client-panel')
            ->login()
            ->brandName('PlagExpert')
            ->colors([
                'primary' => Color::Blue,
                'gray'    => Color::Zinc,
                'danger'  => Color::Rose,
                'success' => Color::Emerald,
                'warning' => Color::Amber,
                'info'    => Color::Sky,
            ])
            ->darkMode(true)
            ->maxContentWidth('xl')
            ->sidebarCollapsibleOnDesktop()
            ->spa()
            ->navigationGroups([])
            ->discoverResources(in: app_path('Filament/Client/Resources'), for: 'App\\Filament\\Client\\Resources')
            ->discoverPages(in: app_path('Filament/Client/Pages'), for: 'App\\Filament\\Client\\Pages')
            ->discoverWidgets(in: app_path('Filament/Client/Widgets'), for: 'App\\Filament\\Client\\Widgets')
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
                FilamentPanelRole::class . ':client',
            ])
            ->authGuard('web')
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
