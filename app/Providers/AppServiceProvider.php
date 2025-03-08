<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Cashier;
use Laravel\Folio\Folio;
use Snelling\FolioMarkdown\Facades\FolioMarkdown;
use Stripe\StripeClient;
use App\Policies\UserPolicy;
use App\Models\User;

class AppServiceProvider extends ServiceProvider
{

    protected $policies = [
        User::class => UserPolicy::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register module service providers
        // Note: Module service providers are now registered in bootstrap/app.php

        $this->app->singleton(StripeClient::class, function ($app) {
            return new StripeClient(config('services.stripe.secret'));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Cashier::useCustomerModel(User::class);

        Folio::path(resource_path('views/pages'))->middleware([
            '*'=>['web']
        ]);

        FolioMarkdown::register();
    }
}
