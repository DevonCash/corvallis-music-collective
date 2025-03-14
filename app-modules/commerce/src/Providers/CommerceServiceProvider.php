<?php

namespace CorvMC\Commerce\Providers;

use Illuminate\Support\ServiceProvider;
use CorvMC\Commerce\Commands\SyncStripeCommand;

class CommerceServiceProvider extends ServiceProvider
{
	public function register(): void
	{
	}
	
	public function boot(): void
	{
		// Register commands
		if ($this->app->runningInConsole()) {
			$this->commands([
				SyncStripeCommand::class,
			]);
		}
		
		// Register views
		$this->loadViewsFrom(__DIR__ . '/../../resources/views', 'commerce');
	}
}
