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
    public static string $label = 'Confirmed';
    public static ?string $verb = 'Confirm';
    public static string $icon = 'heroicon-o-check-circle';
    public static string $color = 'success';
    public static array $allowedTransitions = [CheckedInState::class, CancelledState::class, NoShowState::class];
    
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