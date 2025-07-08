<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Cashier;
use Laravel\Folio\Folio;
use Snelling\FolioMarkdown\Facades\FolioMarkdown;
use Stripe\StripeClient;
use Carbon\Carbon;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

use App\Models\User;


class AppServiceProvider extends ServiceProvider
{



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

        // Test basic connection
        Cashier::useCustomerModel(User::class);

        Folio::path(resource_path('views/pages'))->middleware([
            '*' => ['web']
        ]);

        FolioMarkdown::register();

        // Set default timezone for all date/time operations
        date_default_timezone_set(config('app.timezone'));
    }
}
