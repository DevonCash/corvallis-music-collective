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

    protected array $resources = [];
    protected array $pages = [];
    protected array $widgets = [];

    public function getId(): string
    {
        return 'practice-space';
    }

    public function boot(Panel $panel): void 
    {
    }

    public function resources(array $resources): static
    {
        $this->resources = $resources;
        return $this;
    }

    public function pages(array $pages): static
    {
        $this->pages = $pages;
        return $this;
    }

    public function widgets(array $widgets): static
    {
        $this->widgets = $widgets;
        return $this;
    }
    
    public static function member()
    {
        return static::make()
            ->pages([
                UserBookings::class,
            ]);
    }

    public static function admin()
    {
        return static::make()
            ->resources([
                BookingResource::class,
                RoomResource::class,
                RoomCategoryResource::class,
            ]);
    }

    public function register(Panel $panel): void
    {
        $panel
            ->resources($this->resources)
            ->pages($this->pages)
            ->widgets($this->widgets);
    }

    public static function make() 
    {
        return new (static::class)();
    }
} 