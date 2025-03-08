<?php

namespace CorvMC\StateManagement\Example;

use CorvMC\StateManagement\AbstractState;
use CorvMC\StateManagement\Casts\State;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * This is an example state type class that extends AbstractState.
 * It serves as the base class for all booking states.
 */
abstract class BookingState extends AbstractState
{
    /**
     * List of all available states.
     * This is used for validation and casting.
     */
    protected static array $states = [
        'scheduled' => ScheduledState::class,
        'confirmed' => ConfirmedState::class,
        // These classes don't exist yet, but would be implemented in a real application
        // 'checked_in' => CheckedInState::class,
        // 'completed' => CompletedState::class,
        // 'cancelled' => CancelledState::class,
        // 'no_show' => NoShowState::class,
    ];
    
    /**
     * Resolve a state class from a state name.
     */
    public static function resolveStateClass(string $state): string
    {
        return static::$states[$state] ?? static::$states['scheduled'];
    }
    
    /**
     * Get all available states.
     */
    public static function getAvailableStates(): array
    {
        return static::$states;
    }
    
    /**
     * Cast method for Laravel.
     */
    public static function castUsing(array $arguments): CastsAttributes
    {
        return new State(static::class);
    }
} 