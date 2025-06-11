<?php

namespace CorvMC\StateManagement\Casts;

use CorvMC\StateManagement\AbstractState;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

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
        // If the value is already a state class, return it
        if (is_string($value) && class_exists($value) && is_subclass_of($value, $this->stateTypeClass)) {
            return $value;
        }
        
        // If the value is null or empty, use the first state as default
        if (empty($value)) {
            $states = $this->stateTypeClass::getStates();
            $value = array_key_first($states);
        }
        
        // Validate that the state exists
        $states = $this->stateTypeClass::getStates();
        if (!isset($states[$value])) {
            // Use the first state as default if the current value is invalid
            $value = array_key_first($states);
        }
        
        // Return the state class
        return $states[$value];
    }
    
    /**
     * Prepare the given value for storage.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set($model, string $key, $value, array $attributes): array
    {
        // If the value is a state class, use it directly
        if (is_string($value) && class_exists($value) && is_subclass_of($value, $this->stateTypeClass)) {
            return [$key => $value];
        }
        
        // If the value is a string that matches a state name, get its class
        if (is_string($value) && isset($this->stateTypeClass::getStates()[$value])) {
            return [$key => $this->stateTypeClass::getStates()[$value]];
        }
        
        // Otherwise, use the first state as default
        $states = $this->stateTypeClass::getStates();
        return [$key => reset($states)];
    }
}
