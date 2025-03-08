<?php

namespace CorvMC\PracticeSpace\Models\States\BookingState;

use CorvMC\PracticeSpace\Models\States\BookingState;
use Filament\Forms;
use Illuminate\Database\Eloquent\Model;

/**
 * Confirmed State
 * 
 * This state represents a booking that has been confirmed.
 */
class ConfirmedState extends BookingState
{
    /**
     * The name of the state.
     */
    public static string $name = 'confirmed';
    
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
        return 'Confirmed';
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
        return 'heroicon-o-check-circle';
    }
    
    /**
     * Get the allowed transitions from this state.
     */
    public static function getAllowedTransitions(): array
    {
        return [
            CheckedInState::class,
            CancelledState::class,
            NoShowState::class,
        ];
    }
    
    /**
     * Get the form schema for transitioning to this state.
     */
    public static function getForm(): array
    {
        return [
            Forms\Components\Textarea::make('notes')
                ->label('Confirmation Notes')
                ->placeholder('Add any notes about this confirmation')
                ->required(),
                
            Forms\Components\Toggle::make('send_notification')
                ->label('Send confirmation email to member')
                ->default(true),
        ];
    }
} 