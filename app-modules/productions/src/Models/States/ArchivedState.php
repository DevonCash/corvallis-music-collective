<?php

namespace CorvMC\Productions\Models\States;

use Filament\Forms;
use Illuminate\Database\Eloquent\Model;

/**
 * Archived State
 *
 * This state represents a production that is fully complete and archived.
 * This is a terminal state - no further transitions are allowed.
 */
class ArchivedState extends ProductionState
{
    protected static string $name = 'archived';
    protected static string $label = 'Archived';
    protected static string $icon = 'heroicon-o-archive-box';
    protected static string $color = 'gray';
    protected static array $allowedTransitions = [];

    public static function getForm(): array
    {
        return [
            Forms\Components\Textarea::make('notes')
                ->label('Archive Notes')
                ->placeholder('Add any final notes about the production')
                ->required(false),
        ];
    }

    public static function canTransitionTo(Model $model, string $state): bool
    {
        return in_array($state, static::$allowedTransitions);
    }
} 