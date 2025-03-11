<?php

namespace CorvMC\PracticeSpace\Models\States\BookingState;

use CorvMC\PracticeSpace\Models\States\BookingState;
use Filament\Forms;
use Illuminate\Database\Eloquent\Model;

/**
 * Confirmed State
 * 
 * This state represents a booking that has been confirmed by the member.
 * Staff can check in the member on the day of the booking.
 * If the member doesn't show up, staff can mark the booking as a no-show.
 */
class ConfirmedState extends BookingState
{
    public static string $name = 'confirmed';
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
                ->required(false),
        ];
    }
    
    /**
     * Check if the booking can be checked in.
     */
    public function canBeCheckedIn(): bool
    {
        // Can be checked in on the day of the booking
        if (!$this->model->start_time) {
            return false;
        }
        
        return now()->isSameDay($this->model->start_time);
    }
    
    /**
     * Check if the booking can be marked as a no-show.
     */
    public function canBeMarkedAsNoShow(): bool
    {
        return $this->model->canBeMarkedAsNoShow();
    }
} 