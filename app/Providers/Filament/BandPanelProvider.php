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
use CorvMC\BandProfiles\Models\Band;
use App\Models\User;
use Filament\View\PanelsRenderHook;

class BandPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('band')
            ->path('band')
            ->tenant(Band::class, ownershipRelationship: 'bands')
            ->brandName('Corvallis Music Collective')
            ->brandLogo(fn() => view('filament.brand'))
            ->brandLogoHeight('4rem')
            ->favicon(asset('favicon.svg'))
            ->colors([
                'primary' => [
                    50 => '#fffdf5',
                    100 => '#fff9e0',
                    200 => '#fff3c2',
                    300 => '#ffe28a', // Our brand accent color
                    400 => '#ffd666',
                    500 => '#ffc53d',
                    600 => '#e6a800',
                    700 => '#cc8a00',
                    800 => '#a66f00',
                    900 => '#805500',
                    950 => '#4d3300',
                ],
            ])
            ->discoverResources(in: app_path('Filament/Band/Resources'), for: 'App\\Filament\\Band\\Resources')
            ->discoverPages(in: app_path('Filament/Band/Pages'), for: 'App\\Filament\\Band\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Band/Widgets'), for: 'App\\Filament\\Band\\Widgets')
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
