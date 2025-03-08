<?php

namespace CorvMC\PracticeSpace\Models\States\BookingState;

use CorvMC\PracticeSpace\Models\States\BookingState;
use Filament\Forms;
use Illuminate\Database\Eloquent\Model;

/**
 * CheckedIn State
 * 
 * This state represents a booking where the user has checked in.
 */
class CheckedInState extends BookingState
{
    public static string $label = 'Checked In';
    public static string $icon = 'heroicon-o-user-circle';
    public static string $color = 'info';
    public static array $allowedTransitions = [CompletedState::class];
    
    /**
     * Get the form schema for transitioning to this state.
     */
    public static function getForm(): array
    {
        return [
            Forms\Components\Textarea::make('notes')
                ->label('Check-in Notes')
                ->placeholder('Add any notes about this check-in')
                ->required(),
                
            Forms\Components\DateTimePicker::make('check_in_time')
                ->label('Check-in Time')
                ->default(now())
                ->required(),
        ];
    }
} 