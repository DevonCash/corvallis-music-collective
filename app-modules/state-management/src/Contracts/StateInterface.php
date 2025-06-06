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
     *  Get the verb for the state.
     */
    public static function getVerb(): string;

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
     * Check if this state can transition to another state.
     */
    public function canTransitionTo(string $stateClass): bool;

    /**
     * Transition a model from this state to another state.
     *
     * @param string $stateClass The class name of the target state
     * @param array $data Additional data for the transition
     */
    public function transitionTo(string $stateClass, array $data = []): Model;

    /**
     * Get the form schema for transitioning to this state.
     *
     * @return array<\Filament\Forms\Components\Component>
     */
    public static function getForm(): array;
}
