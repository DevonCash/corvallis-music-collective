<?php

namespace App\Modules\PracticeSpace\Models\States\BookingState;

use AlpineIO\Filament\ModelStates\Concerns\ProvidesSpatieStateToFilament;
use AlpineIO\Filament\ModelStates\Contracts\FilamentSpatieState;
use Filament\Support\Contracts\HasColor;
use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

abstract class BookingState extends State implements FilamentSpatieState, HasColor
{
    use ProvidesSpatieStateToFilament;

    public static string $color = 'primary';

    public function getColor(): string
    {
        return static::$color;
    }

    public static function config(): StateConfig
    {
        return parent::config()
            ->default(Scheduled::class)
            ->allowTransition(Scheduled::class, Confirmed::class, Transitions\ToConfirmed::class)
            ->allowTransition(Confirmed::class, CheckedIn::class, Transitions\ToCheckedIn::class)
            ->allowTransition([Scheduled::class, Confirmed::class], Cancelled::class, Transitions\ToCancelled::class)
            ->allowTransition(Confirmed::class, NoShow::class, Transitions\ToNoShow::class)
            ->allowTransition(CheckedIn::class, Completed::class, Transitions\ToCompleted::class)
        ;
    }
}
