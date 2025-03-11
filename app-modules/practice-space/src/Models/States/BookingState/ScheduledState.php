<?php

namespace CorvMC\PracticeSpace\Models\States\BookingState;

use CorvMC\PracticeSpace\Models\States\BookingState;
use Filament\Forms;
use Illuminate\Database\Eloquent\Model;

/**
 * Scheduled State
 * 
 * This is the initial state for all new bookings.
 * Bookings in this state need to be confirmed by the member within the confirmation window.
 */
class ScheduledState extends BookingState
{
    public static string $name = 'scheduled';
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
                ->label('Booking Notes')
                ->placeholder('Add any notes about this booking')
                ->required(false),
        ];
    }
    
    /**
     * Check if the booking can be confirmed.
     */
    public function canBeConfirmed(): bool
    {
        if (!$this->model->isInConfirmationWindow()) {
            throw new \InvalidArgumentException('Booking cannot be confirmed outside the confirmation window.');
        }
        
        return true;
    }
    
    /**
     * Check if the booking should be auto-cancelled.
     */
    public function shouldBeAutoCancelled(): bool
    {
        return $this->model->isConfirmationDeadlinePassed();
    }
} 