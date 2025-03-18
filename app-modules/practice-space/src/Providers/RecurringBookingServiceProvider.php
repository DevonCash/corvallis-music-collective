<?php

namespace CorvMC\PracticeSpace\Providers;

use CorvMC\PracticeSpace\Services\RecurringBookingService;
use Illuminate\Support\ServiceProvider;

class RecurringBookingServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(RecurringBookingService::class, function ($app) {
            return new RecurringBookingService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
} 