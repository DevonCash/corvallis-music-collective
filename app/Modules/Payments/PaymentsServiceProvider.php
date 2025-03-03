<?php

namespace App\Modules\Payments;

use App\Modules\Payments\Services\PaymentService;
use Illuminate\Support\ServiceProvider;

class PaymentsServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Register the payment service as a singleton
        $this->app->singleton('payment', function ($app) {
            return new PaymentService();
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
} 