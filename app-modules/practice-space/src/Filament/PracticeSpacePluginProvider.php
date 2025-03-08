<?php

namespace CorvMC\PracticeSpace\Filament;

use CorvMC\PracticeSpace\Filament\Resources\BookingResource;
use CorvMC\PracticeSpace\Filament\Resources\RoomCategoryResource;
use CorvMC\PracticeSpace\Filament\Resources\RoomResource;
use Filament\Contracts\Plugin;
use Filament\Panel;

class PracticeSpacePluginProvider implements Plugin
{
    public function getId(): string
    {
        return 'practice-space';
    }

    public function boot(Panel $panel): void 
    {
    }
    
    public function register(Panel $panel): void
    {
        $panel
            ->discoverResources(in: __DIR__ . '/Resources', for: 'CorvMC\\PracticeSpace\\Filament\\Resources')
            ->discoverPages(in: __DIR__ . '/Pages', for: 'CorvMC\\PracticeSpace\\Filament\\Pages')
            ->discoverWidgets(in: __DIR__ . '/Widgets', for: 'CorvMC\\PracticeSpace\\Filament\\Widgets')
            ->resources([
                BookingResource::class,
                RoomResource::class,
                RoomCategoryResource::class,
            ]);
    }

    public static function make() 
    {
        return app(static::class);
    }
} 