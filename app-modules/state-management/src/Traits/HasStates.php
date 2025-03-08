<?php

namespace CorvMC\StateManagement\Traits;

use CorvMC\StateManagement\Contracts\StateInterface;
use CorvMC\StateManagement\Models\StateHistory;
use Filament\Tables\Actions\ActionGroup;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasStates
{
    /**
     * Boot the trait.
     */
    public static function bootHasStates()
    {
        static::creating(function ($model) {
            if (!isset($model->state_type)) {
                // Set default state if not already set
                $states = static::getStates();
                $defaultState = array_key_first($states);
                $model->state_type = $defaultState;
            }
        });

        static::created(function ($model) {
            // Record initial state
            $stateClass = static::getStates()[$model->state_type];
            $state = new $stateClass($model);
            
            // Create initial state history entry
            $model->stateHistory()->create([
                'from_state' => null,
                'to_state' => $model->state_type,
                'reason' => 'Initial state',
            ]);
        });
    }

    /**
     * Get the state history for this model.
     */
    public function stateHistory(): MorphMany
    {
        return $this->morphMany(StateHistory::class, 'model')
            ->orderBy('created_at', 'desc');
    }

    /**
     * Get the current state instance.
     */
    public function getStateAttribute(): StateInterface
    {
        $stateClass = static::getStates()[$this->state_type] ?? array_values(static::getStates())[0];
        return new $stateClass($this);
    }

    /**
     * Get all registered states.
     * 
     * @return array<string, class-string<StateInterface>>
     */
    public static function getStates(): array
    {
        if (!property_exists(static::class, 'states')) {
            throw new \RuntimeException(
                sprintf('The %s class must define a $states property.', static::class)
            );
        }

        return static::$states;
    }

    /**
     * Get all possible transitions from the current state.
     * 
     * @return array<string, string>
     */
    public function getPossibleTransitions(): array
    {
        $stateClass = static::getStates()[$this->state_type];
        return $stateClass::getAllowedTransitions();
    }

    /**
     * Transition to a new state.
     */
    public function transitionTo(string $state, array $data = []): self
    {
        if (!array_key_exists($state, static::getStates())) {
            throw new \InvalidArgumentException(
                sprintf('Invalid state "%s". Available states: %s', $state, implode(', ', array_keys(static::getStates())))
            );
        }

        if (!$this->state->canTransitionTo($state)) {
            throw new \InvalidArgumentException(
                sprintf('Cannot transition from "%s" to "%s"', $this->state_type, $state)
            );
        }

        $stateClass = static::getStates()[$state];
        $stateInstance = new $stateClass($this);
        $stateInstance->enter($this, $data);

        return $this;
    }

    /**
     * Get all state transition actions for Filament.
     */
    public function getStateTransitionActions(): ActionGroup
    {
        $actions = [];
        
        foreach ($this->getPossibleTransitions() as $state => $label) {
            $stateClass = static::getStates()[$state];
            $actions[] = $stateClass::getTableAction($this->state_type);
        }
        
        return ActionGroup::make($actions)
            ->label('Change Status')
            ->icon('heroicon-o-arrow-path')
            ->button();
    }
} 