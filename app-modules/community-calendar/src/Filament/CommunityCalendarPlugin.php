<?php

namespace CorvMC\CommunityCalendar\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;

class CommunityCalendarPlugin implements Plugin
{
    protected const NAMESPACE = 'CorvMC\\CommunityCalendar\\Filament';
    protected const BASE_PATH = __DIR__ . '/Filament';

    protected array $config = [];
    protected string $path;

    public static function admin(): static
    {
        return (new static())
            ->setPath('Admin');
    }

    public static function member(): static
    {
        return (new static())
            ->setPath('Member');
    }

    protected function setPath(string $path): static
    {
        $this->path = $path;
        return $this;
    }

    public function __call(string $name, array $arguments): static
    {
        $this->config[$name] = $arguments[0];
        return $this;
    }

    public function register(Panel $panel): void
    {
        $panel
            ->discoverResources(in: self::BASE_PATH . "/{$this->path}/Resources", for: self::NAMESPACE . "\\{$this->path}\\Resources")
            ->discoverPages(in: self::BASE_PATH . "/{$this->path}/Pages", for: self::NAMESPACE . "\\{$this->path}\\Pages")
            ->discoverWidgets(in: self::BASE_PATH . "/{$this->path}/Widgets", for: self::NAMESPACE . "\\{$this->path}\\Widgets");

        foreach ($this->config as $method => $args) {
            $panel->$method($args);
        }
    }

    public function boot(Panel $panel): void
    {
    }

    public function getId(): string
    {
        return 'community-calendar';
    }
}