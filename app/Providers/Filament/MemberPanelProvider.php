<?php

namespace App\Providers\Filament;

use App\Filament\Member\Resources\BandResource;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
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
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\View\View;
use Filament\Navigation\NavigationBuilder;
use Filament\Facades\Filament;

class MemberPanelProvider extends PanelProvider
{

    public function boot(): void
    {
        FilamentView::registerRenderHook(
            PanelsRenderHook::SIDEBAR_FOOTER,
            fn (): View => view('components.auth-footer')
        );
    }
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id("member")
            ->path("member")
            ->login()
            ->emailVerification()
            ->registration()
            ->profile(isSimple:false)
            ->topbar(false)
            ->colors([
                "primary" => Color::Amber,
            ])
            ->databaseNotifications()
            ->navigation(fn (NavigationBuilder $builder) => $builder->groups([
                NavigationGroup::make("Bands")
                    ->items([
                        ...Filament::auth()->getUser()->bands()->get()->map(fn ($band) => NavigationItem::make($band->name)->url(BandResource::getUrl('edit', [$band->id])))->all(),
                        NavigationItem::make("Create Band")
                            ->icon("heroicon-o-plus")
                            ->url("bands/create"),
                ]),
            ]))
            ->discoverResources(
                in: app_path("Filament/Member/Resources"),
                for: "App\\Filament\\Member\\Resources"
            )
            ->discoverPages(
                in: app_path("Filament/Member/Pages"),
                for: "App\\Filament\\Member\\Pages"
            )
            ->pages([Pages\Dashboard::class])
            ->discoverWidgets(
                in: app_path("Filament/Member/Widgets"),
                for: "App\\Filament\\Member\\Widgets"
            )
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
            ->authMiddleware([Authenticate::class]);
    }
}
