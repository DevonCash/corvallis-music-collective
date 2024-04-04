<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationBuilder;
use Filament\Navigation\NavigationGroup;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use App\Filament\Resources\UserResource;
use Filament\Navigation\NavigationItem;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Blade;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        FilamentView::registerRenderHook(
            PanelsRenderHook::SIDEBAR_FOOTER,
            fn() => Blade::render('@livewire("panel-user-menu")')
        );

        FilamentView::registerRenderHook(
            PanelsRenderHook::PAGE_HEADER_ACTIONS_BEFORE,
            fn() => Blade::render(
                <<<BLADE
<button style="position:absolute; left: .6rem ; --c-300:var(--gray-300);--c-400:var(--gray-400);--c-500:var(--gray-500);--c-600:var(--gray-600);" class="fi-icon-btn relative flex items-center justify-center outline-none transition duration-75 focus-visible:ring-2 disabled:pointer-events-none disabled:opacity-70 -m-1.5 h-9 w-9 text-gray-400 hover:text-gray-500 focus-visible:ring-primary-600 dark:text-gray-500 dark:hover:text-gray-400 dark:focus-visible:ring-primary-500 fi-color-gray fi-topbar-open-sidebar-btn lg:hidden" title="Expand sidebar" type="button" x-data="{}" x-on:click="\$store.sidebar.open()">
        <!--[if BLOCK]><![endif]-->            <span class="sr-only">
                Expand sidebar
            </span>
        <!--[if ENDBLOCK]><![endif]-->

        <!--[if BLOCK]><![endif]-->    <svg class="fi-icon-btn-icon h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
  <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"></path>
</svg><!--[if ENDBLOCK]><![endif]-->

        <!--[if BLOCK]><![endif]--><!--[if ENDBLOCK]><![endif]-->

        <!--[if BLOCK]><![endif]--><!--[if ENDBLOCK]><![endif]-->
    </button>
BLADE
            )
        );

        return $panel
            ->default()
            ->id("admin")
            ->path("admin")
            ->colors([
                "primary" => "#ee8e1d",
            ])
            ->discoverResources(
                in: app_path("Filament/Resources"),
                for: "App\\Filament\\Resources"
            )
            ->discoverPages(
                in: app_path("Filament/Pages"),
                for: "App\\Filament\\Pages"
            )
            ->pages([Pages\Dashboard::class])
            ->discoverWidgets(
                in: app_path("Filament/Widgets"),
                for: "App\\Filament\\Widgets"
            )
            ->widgets([
                Widgets\AccountWidget::class,
                // Widgets\FilamentInfoWidget::class,
            ])
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
            ->authMiddleware([Authenticate::class])
            ->viteTheme("resources/css/filament/admin/theme.css")
            ->topbar(false)
            ->brandLogo(fn() => view("filament.brand"))
            ->brandLogoHeight("64px")
            ->spa(true);
    }
}
