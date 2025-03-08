<?php

namespace CorvMC\PracticeSpace\Models\States\BookingState;

use CorvMC\PracticeSpace\Models\States\BookingState;
use Filament\Forms;
use Illuminate\Database\Eloquent\Model;

/**
 * Completed State
 * 
 * This state represents a booking that has been completed.
 */
class CompletedState extends BookingState
{
    public static string $label = 'Completed';
    public static string $icon = 'heroicon-o-check-badge';
    public static string $color = 'success';
    public static array $allowedTransitions = [];

    /**
     * Get the form schema for transitioning to this state.
     */
    public static function getForm(): array
    {
        return [
            Forms\Components\Textarea::make('notes')
                ->label('Completion Notes')
                ->placeholder('Add any notes about this completion')
                ->required(),
                
            Forms\Components\DateTimePicker::make('check_out_time')
                ->label('Check-out Time')
                ->default(now())
                ->required(),
        ];
    }
} 