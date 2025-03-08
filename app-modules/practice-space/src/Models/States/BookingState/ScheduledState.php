<?php

namespace CorvMC\PracticeSpace\Models\States\BookingState;

use CorvMC\PracticeSpace\Models\States\BookingState;
use Filament\Forms;
use Illuminate\Database\Eloquent\Model;

/**
 * Scheduled State
 * 
 * This is the initial state for all new bookings.
 */
class ScheduledState extends BookingState
{
    public static string $label = 'Scheduled';
    public static string $icon = 'heroicon-o-clock';
    public static string $color = 'warning';
    public static array $allowedTransitions = [ConfirmedState::class, CancelledState::class];
    /**
     * Get the form schema for transitioning to this state.
     */
    public static function getForm(): array
    {
        return [
            Forms\Components\Textarea::make('notes')
                ->label('Scheduling Notes')
                ->placeholder('Add any notes about this scheduling')
                ->required(),
        ];
    }
} 