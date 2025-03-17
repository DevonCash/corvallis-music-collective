<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use CorvMC\Sponsorship\Models\Sponsor;
use App\Models\User;
use Filament\View\PanelsRenderHook;

class SponsorPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('sponsor')
            ->path('sponsor')
            ->tenant(Sponsor::class, ownershipRelationship: 'sponsors')
            ->brandName('Corvallis Music Collective')
            ->brandLogo(fn() => view('filament.brand'))
            ->brandLogoHeight('4rem')
            ->favicon(asset('favicon.svg'))
            ->colors([
                'primary' => [
                    50 => '#e6f7fa',
                    100 => '#cceef5',
                    200 => '#99dcea',
                    300 => '#66cbe0',
                    400 => '#33b9d5',
                    500 => '#00859b', // Our brand secondary color
                    600 => '#006d7f',
                    700 => '#005564',
                    800 => '#003d48',
                    900 => '#00252c',
                    950 => '#000c10',
                ],
            ])
            ->discoverResources(in: app_path('Filament/Sponsor/Resources'), for: 'App\\Filament\\Sponsor\\Resources')
            ->discoverPages(in: app_path('Filament/Sponsor/Pages'), for: 'App\\Filament\\Sponsor\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Sponsor/Widgets'), for: 'App\\Filament\\Sponsor\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
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
            ->authMiddleware([
                Authenticate::class,
            ])
            ->authGuard('web')
            ->databaseTransactions()
            ->renderHook(
                PanelsRenderHook::SIDEBAR_NAV_START,
                fn () => view('filament.components.back-to-member-link')
            )
            ->font('Lexend')
            ->viteTheme('resources/css/filament/member/theme.css')
            ->maxContentWidth('full');
    }
}
