<?php

namespace App\Modules\PracticeSpace\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;

class PracticeSpacePluginProvider implements Plugin
{

    public function getId(): string
    {
        return 'practice-space';
    }

    public function boot(Panel $panel): void {}
    public function register(Panel $panel): void
    {
        $panel
            ->discoverResources(in: __DIR__ . '/Resources', for: 'App\\Modules\\PracticeSpace\\Filament\\Resources');
    }

    public static function make() {
        return app(static::class);
    }
}
