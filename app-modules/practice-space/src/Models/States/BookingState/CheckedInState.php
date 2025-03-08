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
    /**
     * The name of the state.
     */
    public static string $name = 'checked_in';
    
    /**
     * Get the name of the state.
     */
    public static function getName(): string
    {
        return static::$name;
    }
    
    /**
     * Get the display name of the state.
     */
    public static function getLabel(): string
    {
        return 'Checked In';
    }
    
    /**
     * Get the color for Filament UI.
     */
    public static function getColor(): string
    {
        return 'info';
    }
    
    /**
     * Get the icon for Filament UI.
     */
    public static function getIcon(): string
    {
        return 'heroicon-o-user-circle';
    }
    
    /**
     * Get the allowed transitions from this state.
     */
    public static function getAllowedTransitions(): array
    {
        return [
            CompletedState::class,
        ];
    }
    
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