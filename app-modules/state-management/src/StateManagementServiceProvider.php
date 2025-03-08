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
        // Register module-specific services
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // No migrations needed
    }
} 