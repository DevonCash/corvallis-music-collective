<?php

namespace CorvMC\Productions\Models\States;

use Filament\Forms;
use Illuminate\Database\Eloquent\Model;

/**
 * Published State
 *
 * This state represents a production that is publicly shown on the schedule.
 * The production is ready to be viewed by the public and tickets can be sold.
 */
class PublishedState extends ProductionState
{
    protected static string $name = 'published';
    protected static string $label = 'Published';
    protected static string $icon = 'heroicon-o-globe-alt';
    protected static string $color = 'info';
    protected static array $allowedTransitions = [
        'active',
        'cancelled',
        'rescheduled',
    ];

    public static function getAllowedTransitions(): array
    {
        return [
            'active' => 'Start Production',
            'cancelled' => 'Cancel Production',
            'rescheduled' => 'Reschedule Production',
        ];
    }

    public static function getForm(): array
    {
        return [
            Forms\Components\Textarea::make('notes')
                ->label('Publication Notes')
                ->placeholder('Add any notes about the publication')
                ->required(false),
            Forms\Components\Toggle::make('tickets_available')
                ->label('Tickets Available')
                ->helperText('Confirm that tickets are available for purchase')
                ->required(),
        ];
    }

    public function canTransitionTo(string $state): bool
    {
        return in_array($state, static::$allowedTransitions);
    }
} 