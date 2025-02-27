<?php

namespace App\Modules\Payments\Models\States\PaymentState;

use AlpineIO\Filament\ModelStates\Concerns\ProvidesSpatieStateToFilament;
use AlpineIO\Filament\ModelStates\Contracts\FilamentSpatieState;
use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

abstract class PaymentState extends State implements FilamentSpatieState
{
    use ProvidesSpatieStateToFilament;

    public static function config(): StateConfig
    {
        return parent::config()
            ->default(Pending::class)
            ->allowTransition(Pending::class, Paid::class)
            ->allowTransition(Pending::class, Failed::class)
            ->allowTransition(Paid::class, Refunded::class)
            ;
    }
}
