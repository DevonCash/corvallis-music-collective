<?php

namespace App\Modules\Payments\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;

class PaymentsPluginProvider implements Plugin
{
    public function getId(): string
    {
        return 'payments';
    }

    public function boot(Panel $panel): void {}
    
    public function register(Panel $panel): void
    {
        $panel
            ->discoverResources(in: __DIR__ . '/Resources', for: 'App\\Modules\\Payments\\Filament\\Resources');
    }

    public static function make() {
        return app(static::class);
    }
} 