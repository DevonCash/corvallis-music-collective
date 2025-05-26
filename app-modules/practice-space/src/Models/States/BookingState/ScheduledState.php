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

    public function canTransitionTo(string $stateClass): bool
    {
        return match ($stateClass) {
            ConfirmedState::class => $this->canBeConfirmed(),
            CancelledState::class => true,
            default => false,
        };
    }

    /**
     * Check if the booking can be confirmed.
     */
    public function canBeConfirmed(): bool
    {
        $room = $this->model->room;
        $bookingPolicy = $room->booking_policy;

        // Make sure the booking is in the future
        if ($this->model->start_time->isPast()) return false;

        // Check if the booking window is open
        $window = $this->model->start_time->subDays($bookingPolicy->confirmationWindowDays);
        if ($window->isFuture()) return false;

        return true;
    }
}
