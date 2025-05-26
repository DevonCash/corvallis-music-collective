<?php

namespace CorvMC\Productions\Models\States;

use CorvMC\StateManagement\AbstractState;
use CorvMC\StateManagement\Casts\State;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * ProductionState Base Class
 *
 * Purpose: Defines the base class for all production states in the production lifecycle.
 * This class implements the State Pattern using our in-house state management module.
 *
 * State Lifecycle:
 * - Planning: Initial state for all new productions
 * - Published: Production is publicly shown on the schedule
 * - Active: Production is currently happening
 * - Finished: Production has ended, collecting wrap-up stats
 * - Archived: Production is fully complete and archived
 * - Rescheduled: Production has been rescheduled to new dates
 * - Cancelled: Production has been cancelled
 */
abstract class ProductionState extends AbstractState
{
    /**
     * List of all available states.
     * This is used for validation and casting.
     */
    protected static array $states = [
        'planning' => PlanningState::class,
        'published' => PublishedState::class,
        'active' => ActiveState::class,
        'finished' => FinishedState::class,
        'archived' => ArchivedState::class,
        'rescheduled' => RescheduledState::class,
        'cancelled' => CancelledState::class,
    ];

    public static function getStates(): array
    {
        return static::$states;
    }

    public static function getVerb(): string
    {
        return static::$verb ?? static::getLabel();
    }

    public static function onTransitionTo(Model $model, array $data = []): void 
    {
        activity('production_state_transition')
            ->performedOn($model)
            ->causedBy(Auth::user())
            ->withProperties([
                'old' => $model->status->getName(),
                'new' => static::getName(),
                'data' => $data,
            ])
            ->log("Transitioned production #{$model->id} to " . static::getName());
    }
} 