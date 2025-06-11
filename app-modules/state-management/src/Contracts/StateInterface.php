<?php

namespace CorvMC\StateManagement\Contracts;

use Filament\Actions\Action;
use Filament\Infolists\Components\Section;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action as TableAction;
use Illuminate\Database\Eloquent\Model;

interface StateInterface
{
    /**
     * Get the name of the state.
     */
    public static function getName(): string;

    /**
     * Get the label of the state.
     */
    public static function getLabel(): string;

    /**
     * Get the icon of the state.
     */
    public static function getIcon(): string;

    /**
     * Get the color of the state.
     */
    public static function getColor(): string;

    /**
     * Get the verb of the state.
     */
    public static function getVerb(): string;

    /**
     * Get the allowed transitions from this state.
     * @return array<string>
     */
    public static function getAllowedTransitions(Model $model): array;

    /**
     * Get the form schema for transitioning to this state.
     */
    public static function getForm(): array;

    /**
     * Process the form data before transitioning to this state.
     */
    public static function processTransitionForm(array $data): array;

    /**
     * Create a Filament Action for transitioning to this state.
     */
    public static function makeAction(): Action;

    /**
     * Create a Filament TableAction for transitioning to this state.
     */
    public static function makeTableAction(): TableAction;

    /**
     * Check if the state can transition to another state.
     */
    public static function canTransitionTo(Model $model, string $state): bool;

    /**
     * Transition to another state.
     */
    public static function transitionTo(Model $model, string $state, array $data = []): Model;

    /**
     * Get all available states.
     */
    public static function getStates(): array;
}
