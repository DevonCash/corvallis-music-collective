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
  protected Model $model;
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
        return static::class;
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
    

    public static function getVerb(): ?string
    {
        return static::$verb;
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
        dd($model);
        $model->save();
        
        return $model;
    }
    
    public static function getAction(string $stateColumn = 'state'): Action
    {
        $form = static::getForm();
        return Action::make('transition_to_' . static::getName())
            ->visible(fn(Model $record) => $record->{$stateColumn}->canTransitionTo(static::class))
            ->label(static::getVerb() ?? 'Mark as ' . static::getLabel())
            ->icon(static::getIcon())
            ->color(static::getColor())
            ->form($form);
    }

    public static function getTableAction(string $stateColumn = 'state'): \Filament\Tables\Actions\Action
    {
        $form = static::getForm();
        return \Filament\Tables\Actions\Action::make('transition_to_' . static::getName())
            ->visible(fn(Model $record) => $record->{$stateColumn}->canTransitionTo(static::class))
            ->label(static::getVerb() ?? 'Mark as ' . static::getLabel())
            ->icon(static::getIcon())
            ->color(static::getColor())
            ->form($form)
            ->after(function (Model $record, array $data) {
                static::transitionTo($record->state, static::class, $data);
            });
    }

    /**
     * Get Filament actions for transitioning from this state.
     */
    public static function getActions(string $stateColumn = 'state'): array
    {
        $actions = [];
        
        foreach (static::$states as $stateClass) {
            $actions[] = $stateClass::getAction($stateColumn);
        }
        
        return $actions;
    }

    public static function getTableActions(string $stateColumn = 'state'): array
    {
        $actions = [];
        
        foreach (static::$states as $stateClass) {
            $actions[] = $stateClass::getTableAction($stateColumn);
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