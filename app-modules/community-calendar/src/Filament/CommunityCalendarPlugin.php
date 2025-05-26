<?php

namespace CorvMC\CommunityCalendar\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;

class CommunityCalendarPlugin implements Plugin
{

    protected array $resources = [];
    protected array $pages = [];
    protected array $widgets = [];

    public function getId(): string
    {
        return 'community-calendar';
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
            ->resources([
                Resources\CommunityEventResource::class,
            ]);
    }

    public static function admin()
    {
        return static::make()
            ->resources([
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
