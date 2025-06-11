<?php

namespace CorvMC\PracticeSpace\Models\States\BookingState;

use CorvMC\PracticeSpace\Models\States\BookingState;
use Filament\Forms;
use Illuminate\Database\Eloquent\Model;

/**
 * Completed State
 *
 * This state represents a booking that has been completed.
 * This is a terminal state - no further transitions are allowed.
 */
class CompletedState extends BookingState
{
    public static string $name = 'completed';
    public static string $label = 'Completed';
    public static ?string $verb = 'Complete';
    public static string $icon = 'heroicon-o-check-badge';
    public static string $color = 'success';

    /**
     * Get the form schema for transitioning to this state.
     */
    public static function getForm(): array
    {
        return [
            Forms\Components\Textarea::make('notes')
                ->label('Completion Notes')
                ->placeholder('Add any notes about the completed booking')
                ->required(false),

            Forms\Components\DateTimePicker::make('check_out_time')
                ->label('Check-out Time')
                ->default(now())
                ->required(),
        ];
    }
}
