<?php

namespace CorvMC\PracticeSpace\Filament;

use CorvMC\PracticeSpace\Filament\Pages\UserBookings;
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
            ->resources([
                BookingResource::class,
                RoomResource::class,
                RoomCategoryResource::class,
            ])
            ->pages([
                UserBookings::class,
            ]);
    }

    public static function make() 
    {
        return app(static::class);
    }
} 