<?php

namespace CorvMC\Productions\Models\States;

use Filament\Forms;
use Illuminate\Database\Eloquent\Model;

/**
 * Finished State
 *
 * This state represents a production that has ended and needs wrap-up stats.
 * This is where attendance, revenue, and other post-event data is collected.
 */
class FinishedState extends ProductionState
{
    protected static string $name = 'finished';
    protected static string $label = 'Finished';
    protected static string $icon = 'heroicon-o-flag';
    protected static string $color = 'warning';
    protected static array $allowedTransitions = [
        'archived',
    ];

    public static function getAllowedTransitions(): array
    {
        return [
            'archived' => 'Archive Production',
        ];
    }

    public static function getForm(): array
    {
        return [
            Forms\Components\Section::make('Attendance & Revenue')
                ->schema([
                    Forms\Components\TextInput::make('wrap_up_data.total_attendance')
                        ->label('Total Attendance')
                        ->numeric()
                        ->minValue(0)
                        ->required()
                        ->helperText('Total number of people who attended'),
                    Forms\Components\TextInput::make('wrap_up_data.door_donations')
                        ->label('Door Donations')
                        ->numeric()
                        ->prefix('$')
                        ->minValue(0)
                        ->required()
                        ->helperText('Donations collected at the door (goes to bands)'),
                    Forms\Components\TextInput::make('wrap_up_data.counter_donations')
                        ->label('Counter Donations')
                        ->numeric()
                        ->prefix('$')
                        ->minValue(0)
                        ->required()
                        ->helperText('Donations collected at the counter'),
                    Forms\Components\TextInput::make('wrap_up_data.concessions_sales')
                        ->label('Concessions Sales')
                        ->numeric()
                        ->prefix('$')
                        ->minValue(0)
                        ->required()
                        ->helperText('Revenue from concessions sales'),
                ]),
            Forms\Components\Textarea::make('wrap_up_data.notes')
                ->label('Wrap Up Notes')
                ->placeholder('Add any notes about the production')
                ->required(false),
            Forms\Components\Toggle::make('wrap_up_complete')
                ->label('Wrap-up Complete')
                ->helperText('Confirm that all wrap-up data has been collected')
                ->required(),
        ];
    }

    public function canTransitionTo(string $state): bool
    {
        return in_array($state, static::$allowedTransitions);
    }
} 