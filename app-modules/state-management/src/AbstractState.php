<?php

namespace CorvMC\StateManagement;

use CorvMC\StateManagement\Contracts\StateInterface;
use Filament\Actions\Action;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Illuminate\Database\Eloquent\Model;

abstract class AbstractState implements StateInterface
{
  // list of all states, numerically indexed
    protected static array $states = [];

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
    public function __construct(protected Model $model)
    {}
    
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
    public static function canTransitionTo(string $stateClass): bool
    {
        // Get the state name from the class
        $stateName = $stateClass::getName();
        
        return in_array($stateClass, static::getAllowedTransitions());
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
    
    public static function getAction(): Action
    {
        $form = static::getForm();
        return Action::make('transition_to_' . static::getName())
            ->label(static::getLabel())
            ->icon(static::getIcon())
            ->color(static::getColor())
            ->form($form);
    }

    public static function getTableAction(): \Filament\Tables\Actions\Action
    {
        $form = static::getForm();
        return \Filament\Tables\Actions\Action::make('transition_to_' . static::getName())
            ->label(static::getLabel())
            ->icon(static::getIcon())
            ->color(static::getColor())
            ->form($form);
    }

    /**
     * Get Filament actions for transitioning from this state.
     */
    public static function getActions(Model $model): array
    {
        $actions = [];
        
        foreach (static::getAllowedTransitions() as $stateClass) {
            $actions[] = $stateClass::getAction();
        }
        
        return $actions;
    }
    
    /**
     * Create a Filament infolist section for this state.
     */
    public static function getInfolistSection(Model $model): Section
    {
        return Section::make(static::getLabel())
            ->icon(static::getIcon())
            ->description('Current status information')
            ->schema([
                TextEntry::make('state')
                    ->label('Status')
                    ->formatStateUsing(fn () => static::getLabel())
                    ->badge()
                    ->color(static::getColor()),
            ]);
    }
} 