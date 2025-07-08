<?php

namespace CorvMC\StateManagement;

use Illuminate\Support\ServiceProvider;

class StateManagementServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Merge configuration
        $this->mergeConfigFrom(
            __DIR__ . '/../config/state-management.php',
            'state-management'
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Publish configuration file
        $this->publishes([
            __DIR__ . '/../config/state-management.php' => config_path('state-management.php'),
        ], 'state-management-config');

        // Load routes if they exist
        if (file_exists(__DIR__ . '/../routes/state-management-routes.php')) {
            $this->loadRoutesFrom(__DIR__ . '/../routes/state-management-routes.php');
        }

        // Load migrations if they exist
        if (is_dir(__DIR__ . '/../database/migrations')) {
            $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        }
    }
} 