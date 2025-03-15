<?php

namespace App\Providers\Filament;

use CorvMC\PracticeSpace\Filament\PracticeSpacePluginProvider;
use CorvMC\Commerce\Filament\CommercePluginProvider;
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
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use App\Models\User;
use Filament\Http\Middleware\AuthenticateSession;
use Rmsramos\Activitylog\ActivitylogPlugin;
use Saade\FilamentFullCalendar\FilamentFullCalendarPlugin;
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Support\Collection;
use Filament\Navigation\NavigationBuilder;

class MemberPanelProvider extends PanelProvider
{
    protected function getAdminNavigationItems(): array
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        return $user->isAdmin() ? [
            NavigationItem::make('Admin Panel')
                ->url('/admin')
                ->icon('heroicon-o-cog-6-tooth')
        ] : [];
    }

    protected function getSponsorshipsGroup(): array
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $sponsorships = $user->sponsors;
        if($sponsorships->isEmpty()) { 
            return [];
        }
        return [NavigationGroup::make('Business Sponsorships')->items($sponsorships->map(fn($sponsorship) => NavigationItem::make($sponsorship->name)->url("/sponsor/{$sponsorship->id}")))]    ;
    }            

    protected function getBandsGroup(): array
    {
        if (!Auth::check()) {
            return [];
        }
        
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        $items = [];
        
        // Add band items
        foreach ($user->bands as $band) {
            $items[] = NavigationItem::make($band->name)
                ->label($band->name)
                ->url("/band/b/{$band->id}")
                ->icon('heroicon-o-musical-note')
                ->group('My Bands');
        }
        
        // Add sponsor items
        foreach ($user->sponsors as $sponsor) {
            $items[] = NavigationItem::make($sponsor->name)
                ->label($sponsor->name)
                ->url("/sponsor/s/{$sponsor->id}")
                ->icon('heroicon-o-building-office-2')
                ->group('Business Sponsorships');
        }
        
        return [NavigationGroup::make('My Bands')->items($items)];
    }

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('member')
            ->path('member')
            ->login()
            ->registration()
            ->passwordReset()
            ->emailVerification()
            ->brandName('Corvallis Music Collective')
            ->brandLogo(fn() => view('filament.brand'))
            ->brandLogoHeight('4rem')
            ->favicon(asset('favicon.svg'))
            ->font('Lexend')
            ->colors([
                'primary' => Color::Amber,
            ])
            ->sidebarWidth('15rem')
            ->discoverResources(in: app_path('Filament/Member/Resources'), for: 'App\\Filament\\Member\\Resources')
            ->discoverPages(in: app_path('Filament/Member/Pages'), for: 'App\\Filament\\Member\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->plugins([
                ActivitylogPlugin::make()
                    ->navigationItem(false),
                FilamentFullCalendarPlugin::make(),
                PracticeSpacePluginProvider::make(),
                CommercePluginProvider::make(),
            ])
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
            ->navigation(function (NavigationBuilder $builder) use ($panel): NavigationBuilder {
                // dd($panel);
                $hasPages = collect([...$panel->getPages(), ...$panel->getResources()]);
                $items = $hasPages->map(fn($page) => $page::getNavigationItems())->flatten();
                $groups = $items->filter(fn($item) => $item->getGroup() !== null)->groupBy(fn($item) => $item->getGroup());
                return $builder
                    ->groups([
                        ...$groups->map(fn($group) => NavigationGroup::make($group->first()->getGroup())->items($group->toArray())),
                        ...$this->getBandsGroup(),
                        ...$this->getSponsorshipsGroup(),
                    ])
                    ->items([
                        ...$items->filter(fn($item) => $item->getGroup() === null),
                        ...($this->getAdminNavigationItems()),
                    ]);
            })
            ->databaseNotifications()
            ->viteTheme('resources/css/filament/member/theme.css')
            ->maxContentWidth('full');
    }
}
