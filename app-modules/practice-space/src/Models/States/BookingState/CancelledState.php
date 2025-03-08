<?php

namespace CorvMC\PracticeSpace\Models\States\BookingState;

use CorvMC\PracticeSpace\Models\States\BookingState;
use Filament\Forms;
use Illuminate\Database\Eloquent\Model;

/**
 * Cancelled State
 * 
 * This state represents a booking that has been cancelled.
 */
class CancelledState extends BookingState
{
    /**
     * The name of the state.
     */
    public static string $name = 'cancelled';
    
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
        return 'Cancelled';
    }
    
    /**
     * Get the color for Filament UI.
     */
    public static function getColor(): string
    {
        return 'danger';
    }
    
    /**
     * Get the icon for Filament UI.
     */
    public static function getIcon(): string
    {
        return 'heroicon-o-x-circle';
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
                ->label('Cancellation Reason')
                ->placeholder('Add the reason for cancellation')
                ->required(),
                
            Forms\Components\Toggle::make('refund_payment')
                ->label('Refund payment to member')
                ->default(false),
        ];
    }
} 