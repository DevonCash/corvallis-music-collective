<?php

namespace CorvMC\PracticeSpace;

use Illuminate\Support\ServiceProvider;

class PracticeSpaceServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register module-specific services
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        
        // Load views
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'practice-space');
        
        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        
        // Load translations
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'practice-space');
        
    }
} 