<?php

namespace CorvMC\Commerce\Filament;

use CorvMC\Commerce\Filament\Resources\MembershipPlanResource;
use Filament\Contracts\Plugin;
use Filament\Panel;
use CorvMC\Commerce\Filament\Pages\ManageMembership;
class CommercePluginProvider implements Plugin
{
    public function getId(): string
    {
        return 'commerce';
    }

    public function boot(Panel $panel): void
    {
    }

    public function register(Panel $panel): void
    {
        $panel
            ->resources([
            ])
            ->pages([
            ]);
    }

    public static function make()
    {
        return app(static::class);
    }
}
