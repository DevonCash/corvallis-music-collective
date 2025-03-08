<?php

namespace CorvMC\StateManagement\Contracts;

use Filament\Actions\Action;
use Filament\Infolists\Components\Section;
use Illuminate\Database\Eloquent\Model;

interface StateInterface
{
    /**
     * Get the name of the state.
     */
    public static function getName(): string;
    
    /**
     * Get the display name of the state.
     */
    public static function getLabel(): string;
    
    /**
     * Get the color for Filament UI.
     */
    public static function getColor(): string;
    
    /**
     * Get the icon for Filament UI.
     */
    public static function getIcon(): string;
    
    /**
     * Get the allowed transitions from this state.
     * 
     * @return array<string, string> Array of state name => transition label
     */
    public static function getAllowedTransitions(): array;
   
    /**
     * Check if this state can transition to another state.
     */
    public static function canTransitionTo(string $stateClass): bool;
    
    /**
     * Transition a model from this state to another state.
     * 
     * @param Model $model The model to transition
     * @param string $stateClass The class name of the target state
     * @param array $data Additional data for the transition
     */
    public static function transitionTo(Model $model, string $stateClass, array $data = []): Model;
    
    /**
     * Get Filament actions for transitioning from this state.
     */
    public static function getActions(): array;
    
    /**
     * Get the form schema for transitioning to this state.
     * 
     * @return array<\Filament\Forms\Components\Component>
     */
    public static function getForm(): array;

    /**
     * Create a Filament infolist section for this state.
     */
    public static function getInfolistSection(Model $model): Section;
} 