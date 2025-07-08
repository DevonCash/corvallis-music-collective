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
        // dd([
        //     'current_schema' => DB::select('SELECT current_schema()'),
        //     'search_path' => DB::select('SHOW search_path'),
        //     'sessions_in_public' => DB::select("SELECT schemaname FROM pg_tables WHERE tablename = 'sessions'"),
        //     'all_schemas' => DB::select('SELECT schema_name FROM information_schema.schemata')
        // ]);
        Cashier::useCustomerModel(User::class);

        Folio::path(resource_path('views/pages'))->middleware([
            '*' => ['web']
        ]);

        FolioMarkdown::register();

        // Set default timezone for all date/time operations
        date_default_timezone_set(config('app.timezone'));
    }
}
