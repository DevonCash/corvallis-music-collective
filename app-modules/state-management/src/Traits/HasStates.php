<?php

namespace CorvMC\StateManagement\Traits;

use Illuminate\Database\Eloquent\Model;
use CorvMC\StateManagement\Casts\State;
use CorvMC\StateManagement\AbstractState;

trait HasStates
{
    /**
     * Get the state column name.
     */
    public function getStateColumn(): string
    {
        return 'status';
    }

    /**
     * Get the current state instance.
     */
    public function getStateAttribute(): AbstractState
    {
        $stateClass = $this->getAttribute($this->getStateColumn());
        return new $stateClass();
    }

    /**
     * Set the state.
     */
    public function setStateAttribute(AbstractState|string $value): void
    {
        if ($value instanceof AbstractState) {
            $value = get_class($value);
        }
        $this->setAttribute($this->getStateColumn(), $value);
    }

    /**
     * Get all possible transitions from the current state.
     * @return array<string>
     */
    public function getPossibleTransitions(): array
    {
        $currentState = $this->getStateAttribute();
        return $currentState::getAllowedTransitions($this);
    }

    /**
     * Check if the model can transition to the given state.
     */
    public function canTransitionTo(AbstractState|string $state): bool
    {
        $currentState = $this->getStateAttribute();
        if ($state instanceof AbstractState) {
            $state = get_class($state);
        }
        return $currentState::canTransitionTo($this, $state);
    }

    /**
     * Transition to a new state.
     */
    public function transitionTo(AbstractState|string $state, array $data = []): Model
    {
        $currentState = $this->getStateAttribute();
        if ($state instanceof AbstractState) {
            $state = get_class($state);
        }
        return $currentState::transitionTo($this, $state, $data);
    }

    /**
     * Get the state class for this model.
     */
    protected function getStateClass(): string
    {
        return $this->stateClass ?? AbstractState::class;
    }
}
