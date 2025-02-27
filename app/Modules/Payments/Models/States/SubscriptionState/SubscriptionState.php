<?php

namespace App\Modules\Payments\Models\States\SubscriptionState;

use AlpineIO\Filament\ModelStates\Concerns\ProvidesSpatieStateToFilament;
use AlpineIO\Filament\ModelStates\Contracts\FilamentSpatieState;
use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

abstract class SubscriptionState extends State implements FilamentSpatieState
{
    use ProvidesSpatieStateToFilament;
    public static function config(): StateConfig
    {
        return parent::config();
    }
}
