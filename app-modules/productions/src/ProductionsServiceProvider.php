<?php

namespace CorvMC\Productions;

use CorvMC\Productions\Filament\Resources\ProductionResource;
use CorvMC\Productions\Filament\Resources\VenueResource;
use CorvMC\Productions\Console\Commands\FinishActiveProductions;
use Filament\Panel;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class ProductionsServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->commands([
            FinishActiveProductions::class,
        ]);
    }

    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        // Register translations
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'productions');

        // Register seeders
        // $this->loadSeedersFrom(__DIR__.'/../database/seeders');

        // Register Filament resources
        $this->app->booted(function () {
            $this->registerFilamentResources();
        });

        // Register Livewire components
        if (class_exists(Livewire::class)) {
            Livewire::component('productions::productions-index', \CorvMC\Productions\Livewire\ProductionsIndex::class);
        }
    }

    protected function registerFilamentResources(): void
    {
        if (!class_exists(Panel::class)) {
            return;
        }

        Panel::configureUsing(function (Panel $panel) {
            $panel->resources([
                ProductionResource::class,
                VenueResource::class,
            ]);
        });
    }
} 