<?php

namespace App\Modules\PracticeSpace\Models\States\BookingState;

use AlpineIO\Filament\ModelStates\Concerns\ProvidesSpatieStateToFilament;
use AlpineIO\Filament\ModelStates\Contracts\FilamentSpatieState;
use Filament\Support\Contracts\HasColor;
use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

/**
 * BookingState Base Class
 *
 * Purpose: Defines the base class for all booking states in the practice space booking lifecycle.
 * This class implements the State Pattern using Spatie's Model States package and provides
 * integration with Filament UI components.
 *
 * State Lifecycle:
 * - Scheduled: Initial state for all new bookings
 * - Confirmed: Booking has been confirmed (within 3 days of start time)
 * - CheckedIn: User has arrived and checked in to the practice space
 * - Completed: Practice session has been successfully completed
 * - Cancelled: Booking was cancelled before it occurred (from Scheduled or Confirmed)
 * - NoShow: User did not arrive for their confirmed booking
 *
 * Each state has specific entry and exit conditions defined in their respective classes
 * and transitions between states are managed by the Transition classes in the Transitions namespace.
 */
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
