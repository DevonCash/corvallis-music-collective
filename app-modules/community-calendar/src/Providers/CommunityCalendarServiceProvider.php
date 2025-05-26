<?php

namespace CorvMC\CommunityCalendar\Providers;

use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\ServiceProvider;

class CommunityCalendarServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/community-calendar-routes.php');
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'community-calendar');
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        $this->mergeConfigFrom(__DIR__ . '/../../config/community-calendar.php', 'community-calendar');

        // Publish resources
        $this->publishes([
            __DIR__ . '/../../resources/views' => resource_path('views/vendor/community-calendar'),
            __DIR__ . '/../../config/community-calendar.php' => config_path('community-calendar.php'),
        ], 'community-calendar');

        FilamentAsset::register([
            Js::make('navigation-monitor', path: asset('js/navigation-monitor.js'))
        ]);
    }
}
