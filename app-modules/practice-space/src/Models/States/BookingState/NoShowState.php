<?php

namespace CorvMC\PracticeSpace\Models\States\BookingState;

use CorvMC\PracticeSpace\Models\States\BookingState;
use Filament\Forms;
use Illuminate\Database\Eloquent\Model;

/**
 * NoShow State
 * 
 * This state represents a booking where the user did not show up.
 */
class NoShowState extends BookingState
{
    /**
     * The name of the state.
     */
    public static string $name = 'no_show';
    
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
        return 'No Show';
    }
    
    /**
     * Get the color for Filament UI.
     */
    public static function getColor(): string
    {
        return 'gray';
    }
    
    /**
     * Get the icon for Filament UI.
     */
    public static function getIcon(): string
    {
        return 'heroicon-o-user-minus';
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
                ->label('No-Show Notes')
                ->placeholder('Add any notes about this no-show')
                ->required(),
                
            Forms\Components\Toggle::make('charge_cancellation_fee')
                ->label('Charge cancellation fee')
                ->default(true),
        ];
    }
} 