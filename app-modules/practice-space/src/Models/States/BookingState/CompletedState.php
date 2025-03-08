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
    /**
     * The name of the state.
     */
    public static string $name = 'completed';
    
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
        return 'Completed';
    }
    
    /**
     * Get the color for Filament UI.
     */
    public static function getColor(): string
    {
        return 'success';
    }
    
    /**
     * Get the icon for Filament UI.
     */
    public static function getIcon(): string
    {
        return 'heroicon-o-check-badge';
    }
    
    /**
     * Get the allowed transitions from this state.
     */
    public static function getAllowedTransitions(): array
    {
        return [];
    }
    
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