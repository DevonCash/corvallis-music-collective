<?php

namespace CorvMC\StateManagement;

use CorvMC\StateManagement\Casts\State;
use CorvMC\StateManagement\Contracts\StateInterface;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

abstract class AbstractState implements StateInterface
{
    // list of all states, numerically indexed
    protected Model $model;
    protected static string $name = '';
    protected static ?string $verb = null;
    protected static string $label;
    protected static string $icon = 'heroicon-o-check-circle';
    protected static string $color = 'gray';
    protected static array $allowedTransitions = [];
    protected static array $states = [];
    protected static array $children = [];

    // Return list of all states indexed by name
    public static function getStates(): array
    {
        // Return list of all states indexed by name
        $states = [];
        foreach (static::$states as $state) {
            $states[$state::getName()] = $state;
        }
        return $states;
    }

    /**
     * Create a new state instance.
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    public static function addChild(string $child)
    {
        static::$children[] = $child;
    }


    public static function getName(): string
    {
        if (static::$name === '') {
            throw new \Exception('State name is not set: ' . static::class);
        }
        return static::$name;
    }

    public static function getLabel(): string
    {
        return static::$label;
    }

    public static function getIcon(): string
    {
        return static::$icon;
    }

    public static function getColor(): string
    {
        return static::$color;
    }

    /**
     * Get the form schema for transitioning to this state.
     *
     * @return array<\Filament\Forms\Components\Component>
     */
    public static function getForm(): array
    {
        // Default form schema - can be overridden by specific states
        return [];
    }

    /**
     * Check if this state can transition to another state.
     */
    public function canTransitionTo(string $stateClass): bool
    {
        // Get the state name from the class
        return false;
    }


    public static function getVerb(): string
    {
        return static::$verb ?? 'Mark as ' . static::getLabel();
    }

    /**
     * Transition a model from this state to another state.
     */
    public function transitionTo(string $stateClass, array $data = []): Model
    {
        if (!$this->canTransitionTo($stateClass)) {
            throw new \InvalidArgumentException(
                sprintf('Cannot transition from "%s" to "%s"', static::getName(), $stateClass::getName())
            );
        }

        $stateClass::onTransitionTo($this->model, $data);
        // Update the model state
        $this->model->state = $stateClass::getName();
        $this->model->save();

        return $this->model;
    }

    public static function onTransitionTo(Model $model, array $data = []): void
    {
        // Default implementation - can be overridden by specific states
    }


    /**
     * Cast method for Laravel.
     */
    public static function castUsing(array $arguments): CastsAttributes
    {
        return new State(static::class);
    }

}
