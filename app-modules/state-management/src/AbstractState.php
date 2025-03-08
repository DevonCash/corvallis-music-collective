<?php

namespace CorvMC\StateManagement;

use CorvMC\StateManagement\Contracts\StateInterface;
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

        $parent = get_parent_class(static::class);
    }

    public static function addChild(string $child)
    {
        static::$children[] = $child;
    }
    

    public static function getName(): string
    {
        if(static::$name === '') {
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
    
    public static function getAllowedTransitions(): array
    {
        return static::$allowedTransitions;
    }
    /**
     * Check if this state can transition to another state.
     */
    public static function canTransitionTo(string $stateClass): bool
    {
        // Get the state name from the class
        $stateName = $stateClass::getName();
        
        return in_array($stateClass, static::getAllowedTransitions());
    }
    

    public static function getVerb(): string
    {
        return static::$verb ?? 'Mark as ' . static::getLabel();
    }

    /**
     * Transition a model from this state to another state.
     */
    public static function transitionTo(Model $model, string $stateClass, array $data = []): Model
    {
        if (!static::canTransitionTo($stateClass)) {
            throw new \InvalidArgumentException(
                sprintf('Cannot transition from "%s" to "%s"', static::getName(), $stateClass::getName())
            );
        }
        // Update the model state
        $model->state = $stateClass::getName();
        $model->save();
        
        return $model;
    }
} 