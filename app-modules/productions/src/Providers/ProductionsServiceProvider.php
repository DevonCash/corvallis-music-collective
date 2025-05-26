<?php

namespace CorvMC\Productions\Providers;

use Illuminate\Support\ServiceProvider;

class ProductionsServiceProvider extends ServiceProvider
{
	public function register(): void
	{
	}
	
	public function boot(): void
	{
		$this->loadMigrationsFrom(__DIR__.'/../database/migrations');
		
		// Only load routes if the file exists
		if (file_exists(__DIR__.'/../routes/web.php')) {
			$this->loadRoutesFrom(__DIR__.'/../routes/web.php');
		}

		// Publish assets
		$this->publishes([
			__DIR__.'/../resources/css' => public_path('vendor/productions/css'),
		], 'productions-assets');

		// Register Filament resources
		$this->app->booted(function () {
			$this->registerFilamentResources();
		});
	}

	protected function registerFilamentResources(): void
	{
		if (!class_exists(\Filament\Panel::class)) {
			return;
		}

		\Filament\Panel::configureUsing(function (\Filament\Panel $panel) {
			$panel
				->resources([
					\CorvMC\Productions\Filament\Resources\ProductionResource::class,
					\CorvMC\Productions\Filament\Resources\VenueResource::class,
				]);
		});
	}
}
