<?php

namespace CorvMC\StateManagement;

use CorvMC\StateManagement\Casts\State;
use CorvMC\StateManagement\Contracts\StateInterface;
use CorvMC\StateManagement\Exceptions\InvalidStateTransitionException;
use CorvMC\StateManagement\Exceptions\StateConfigurationException;
use CorvMC\StateManagement\Logging\StateLogger;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Filament\Actions\Action;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Tables\Actions\ActionGroup;
use Filament\Forms;
use Illuminate\Support\Facades\Log;

abstract class AbstractState implements StateInterface
{
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

    public static function addChild(string $child)
    {
        static::$children[] = $child;
    }

    /**
     * Get the name of the state.
     */
    public static function getName(): string
    {
        if (static::$name === '') {
            StateLogger::logConfigurationError(
                'State name is not set',
                static::class
            );
            throw new StateConfigurationException(
                'State name is not set',
                static::class
            );
        }
        return static::$name;
    }

    /**
     * Get the label of the state.
     */
    public static function getLabel(): string
    {
        return static::$label;
    }

    /**
     * Get the icon of the state.
     */
    public static function getIcon(): string
    {
        return static::$icon;
    }

    /**
     * Get the color of the state.
     */
    public static function getColor(): string
    {
        return static::$color;
    }

    /**
     * Get the verb of the state.
     */
    public static function getVerb(): string
    {
        return static::$verb ?? static::getLabel();
    }

    /**
     * Get the form schema for transitioning to this state.
     * By default, states don't require any additional data.
     */
    public static function getForm(): array
    {
        return [];
    }

    /**
     * Process the form data before transitioning to this state.
     * By default, states don't process any data.
     */
    public static function processTransitionForm(array $data): array
    {
        return [];
    }

    /**
     * Create an action for transitioning to this state.
     */
    public static function makeAction(): Action
    {
        $action = Action::make(static::getName())
            ->label(static::getVerb())
            ->icon(static::getIcon())
            ->color(static::getColor())
            ->requiresConfirmation();

        // Only add form if the state defines one
        if (!empty(static::getForm())) {
            $action->form(static::getForm());
        }

        return $action->action(function (Model $model, array $data): void {
            $processedData = static::processTransitionForm($data);
            $model->transitionTo(static::class, $processedData);
        });
    }

    /**
     * Create a table action for transitioning to this state.
     */
    public static function makeTableAction(): TableAction
    {
        $action = TableAction::make(static::getName())
            ->label(static::getVerb())
            ->icon(static::getIcon())
            ->color(static::getColor())
            ->requiresConfirmation();

        // Only add form if the state defines one
        if (!empty(static::getForm())) {
            $action->form(static::getForm());
        }

        return $action->action(function ($record, array $data): void {
            $processedData = static::processTransitionForm($data);
            $record->transitionTo(static::class, $processedData);
        });
    }

    /**
     * Check if a model can transition to a given state.
     */
    public static function canTransitionTo(Model $model, string $state): bool
    {
        // If the state is a name, resolve it to a class
        if (!class_exists($state)) {
            $state = static::resolveStateClass($state);
        }

        return in_array($state, static::getAllowedTransitions($model));
    }

    /**
     * Transition to another state.
     */
    public static function transitionTo(Model $model, string $state, array $data = []): Model
    {
        $fromState = static::getName();
        $toState = class_exists($state) ? $state::getName() : $state;
        
        if (!static::canTransitionTo($model, $state)) {
            $exception = new InvalidStateTransitionException(
                $fromState,
                $toState,
                $model,
                'This transition is not allowed'
            );
            
            StateLogger::logTransitionError($model, $fromState, $toState, $exception);
            throw $exception;
        }

        // If the state is a class name, get its name
        $stateName = class_exists($state) ? $state::getName() : $state;
        $stateClass = class_exists($state) ? $state : static::getStates()[$stateName];
        $stateClass::onTransitionTo($model, $data);

        $model->state = $stateName;
        $model->save();

        // Log successful transition
        StateLogger::logTransition($model, $fromState, $toState, $data);

        return $model;
    }

    public static function onTransitionTo(Model $model, array $data = []): void
    {
        // Default implementation - can be overridden by specific states
    }

    /**
     * Get the allowed transitions from this state.
     * @return array<string>
     */
    public static function getAllowedTransitions(Model $model): array
    {
        return [];
    }

    /**
     * Cast method for Laravel.
     */
    public static function castUsing(array $arguments): CastsAttributes
    {
        return new State(static::class);
    }

    /**
     * Create transition actions for all states in this state type.
     */
    public static function makeTransitionActions(string $stateColumn = 'state'): array
    {
        $actions = [];
        foreach (static::getStates() as $stateName => $stateClass) {
            $action = $stateClass::makeAction()
                ->visible(fn (Model $record) => $record->canTransitionTo($stateClass));
            $actions[] = $action;
        }
        return $actions;
    }

    /**
     * Create transition table actions for all states in this state type.
     */
    public static function makeTransitionTableActions(string $stateColumn = 'state'): array
    {
        $actions = [];
        foreach (static::getStates() as $stateName => $stateClass) {
            $action = $stateClass::makeTableAction()
                ->visible(fn (Model $record) => $record->canTransitionTo($stateClass));
            $actions[] = $action;
        }
        return $actions;
    }

    /**
     * Create a group of transition actions for all states in this state type.
     */
    public static function makeTransitionActionGroup(string $stateColumn = 'state'): ActionGroup
    {
        return ActionGroup::make(static::makeTransitionActions($stateColumn));
    }

    /**
     * Create a group of transition table actions for all states in this state type.
     */
    public static function makeTransitionTableActionGroup(string $stateColumn = 'state'): ActionGroup
    {
        return ActionGroup::make(static::makeTransitionTableActions($stateColumn));
    }

    /**
     * Resolve a state class from a state name.
     */
    public static function resolveStateClass(string $state): string
    {
        return static::$states[$state] ?? static::$states['scheduled'];
    }
}
