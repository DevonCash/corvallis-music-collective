<?php

namespace CorvMC\PracticeSpace\Models\States;

use CorvMC\StateManagement\AbstractState;
use CorvMC\StateManagement\Casts\State;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Filament\Tables\Actions\Action;
use CorvMC\PracticeSpace\Models\Booking;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * BookingState Base Class
 *
 * Purpose: Defines the base class for all booking states in the practice space booking lifecycle.
 * This class implements the State Pattern using our in-house state management module.
 *
 * State Lifecycle:
 * - Scheduled: Initial state for all new bookings
 * - Confirmed: Booking has been confirmed (within 3 days of start time)
 * - CheckedIn: User has arrived and checked in to the practice space
 * - Completed: Practice session has been successfully completed
 * - Cancelled: Booking was cancelled before it occurred (from Scheduled or Confirmed)
 * - NoShow: User did not arrive for their confirmed booking
 */
abstract class BookingState extends AbstractState
{
    /**
     * List of all available states.
     * This is used for validation and casting.
     */
    protected static array $states = [
        BookingState\ScheduledState::class,
        BookingState\ConfirmedState::class,
        BookingState\CheckedInState::class,
        BookingState\CompletedState::class,
        BookingState\CancelledState::class,
        BookingState\NoShowState::class,
    ];


    public static function onTransitionTo(Model $model, array $data = []): void {
        activity('booking_state_transition')
        ->performedOn($model)
        ->causedBy(Auth::user())
        ->withProperties([
            'old' => $model->state->getName(),
            'new' => static::getName(),
            'data' => $data,
        ])
        ->log("Transitioned booking #{$model->id} to " . static::getName());
    }
}
