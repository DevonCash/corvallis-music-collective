<?php

namespace App\Providers;

use App\Modules\User\Models\User;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Cashier;
use Laravel\Folio\Folio;
use Snelling\FolioMarkdown\Facades\FolioMarkdown;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register module service providers
        // Note: Module service providers are now registered in bootstrap/app.php
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
