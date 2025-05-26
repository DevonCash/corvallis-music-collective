<?php

namespace CorvMC\Productions\Models\States;

use Filament\Forms;
use Illuminate\Database\Eloquent\Model;

/**
 * Active State
 *
 * This state represents a production that is currently happening.
 * The event is in progress and being managed.
 */
class ActiveState extends ProductionState
{
    protected static string $name = 'active';
    protected static string $label = 'Active';
    protected static string $icon = 'heroicon-o-play';
    protected static string $color = 'success';
    protected static array $allowedTransitions = [
        FinishedState::class,
        CancelledState::class,
    ];

    public static function getAllowedTransitions(): array
    {
        return [
            FinishedState::class => 'End Production',
            CancelledState::class => 'Cancel Production',
        ];
    }

    public static function getForm(): array
    {
        return [
            Forms\Components\Textarea::make('notes')
                ->label('Active Notes')
                ->placeholder('Add any notes about the active production')
                ->required(false),
            Forms\Components\DateTimePicker::make('ended_at')
                ->label('End Time')
                ->default(now())
                ->required(),
        ];
    }

    public function canTransitionTo(string|object $state): bool
    {
        if (is_object($state)) {
            $state = get_class($state);
        }
        return in_array($state, static::$allowedTransitions);
    }
} 