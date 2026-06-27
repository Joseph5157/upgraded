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

class VendorPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('vendor')
            ->path('vendor-panel')
            ->login()
            ->brandName('PlagExpert')
            ->darkMode(false)
            ->maxContentWidth('xl')
            ->sidebarCollapsibleOnDesktop()
            ->spa()
            ->navigationGroups([])
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Vendor/Resources'), for: 'App\\Filament\\Vendor\\Resources')
            ->discoverPages(in: app_path('Filament/Vendor/Pages'), for: 'App\\Filament\\Vendor\\Pages')
            ->discoverWidgets(in: app_path('Filament/Vendor/Widgets'), for: 'App\\Filament\\Vendor\\Widgets')
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
                FilamentPanelRole::class . ':vendor',
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
