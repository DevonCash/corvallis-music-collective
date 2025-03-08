<?php

namespace CorvMC\StateManagement\Casts;

use CorvMC\StateManagement\AbstractState;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class State implements CastsAttributes
{
    /**
     * The state type class that extends AbstractState.
     */
    protected string $stateTypeClass;
    
    /**
     * Create a new cast instance.
     */
    public function __construct(string $stateTypeClass)
    {
        $this->stateTypeClass = $stateTypeClass;
        
        // Validate that the class extends AbstractState and has a states property
        if (!is_subclass_of($stateTypeClass, AbstractState::class)) {
            throw new \InvalidArgumentException("Class {$stateTypeClass} must extend " . AbstractState::class);
        }
        
        if (!method_exists($stateTypeClass, 'getStates')) {
            throw new \InvalidArgumentException("Class {$stateTypeClass} must have a method getStates()");
        }
    }
    
    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get($model, string $key, $value, array $attributes)
    {
        
        // Validate that the state exists
        $states = $this->stateTypeClass::getAvailableStates();
        if (!isset($states[$value])) {
            // Use the first state as default if the current value is invalid
            $value = array_key_first($states);
        }
        
        // Return the state name
        return $states[$value];
    }
    
    /**
     * Prepare the given value for storage.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set($model, string $key, $value, array $attributes): array
    {
        // If the value is an instance of AbstractState, get its name
        if ($value instanceof $this->stateTypeClass) {
            return [$key => $value::getName()];
        }
        
        // If the value is a class name that extends AbstractState, get its name
        if (is_string($value) && class_exists($value) && is_subclass_of($value, $this->stateTypeClass)) {
            return [$key => $value::getName()];
        }
        
        // Otherwise, just use the value as is (assuming it's a valid state name)
        return [$key => $value];
    }
}
