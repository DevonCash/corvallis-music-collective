<?php

namespace CorvMC\PracticeSpace\Models\States\BookingState;

use CorvMC\PracticeSpace\Models\States\BookingState;
use Filament\Forms;
use Illuminate\Database\Eloquent\Model;

/**
 * CheckedIn State
 *
 * This state represents a booking where the user has been checked in by staff.
 * Staff can mark the booking as completed when the member is finished.
 */
class CheckedInState extends BookingState
{
    public static string $name = 'checked_in';
    public static string $label = 'Checked In';
    public static ?string $verb = 'Check In';
    public static string $icon = 'heroicon-o-user-circle';
    public static string $color = 'info';

    /**
     * Get the form schema for transitioning to this state.
     */
    public static function getForm(): array
    {
        return [

            Forms\Components\DateTimePicker::make('check_in_time')
                ->label('Check-in Time')
                ->default(now())
                ->required(),

            Forms\Components\Toggle::make('payment_completed')
                ->label('Payment Completed')
                ->helperText('Mark if the member has paid for this booking')
                ->default(false),

        ];
    }

    public function canTransitionTo(string $state): bool
    {
        return match ($state) {
            CompletedState::class => true,
            default => false,
        };
    }
}
