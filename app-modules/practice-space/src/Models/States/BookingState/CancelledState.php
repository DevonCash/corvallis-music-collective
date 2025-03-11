<?php

namespace CorvMC\PracticeSpace\Models\States\BookingState;

use CorvMC\PracticeSpace\Models\States\BookingState;
use Filament\Forms;
use Illuminate\Database\Eloquent\Model;

/**
 * Cancelled State
 * 
 * This state represents a booking that has been cancelled.
 * Bookings can be cancelled manually by staff or members, or automatically if not confirmed.
 * This is a terminal state - no further transitions are allowed.
 */
class CancelledState extends BookingState
{
    /**
     * The name of the state.
     */
    public static string $name = 'cancelled';
    public static ?string $verb = 'Cancel';
    public static string $label = 'Cancelled';
    public static string $icon = 'heroicon-o-x-circle';
    public static string $color = 'danger';
    public static array $allowedTransitions = [];

    /**
     * Get the form schema for transitioning to this state.
     */
    public static function getForm(): array
    {
        return [
            Forms\Components\Textarea::make('cancellation_reason')
                ->label('Cancellation Reason')
                ->placeholder('Provide a reason for cancellation')
                ->required(),
                
            Forms\Components\DateTimePicker::make('cancelled_at')
                ->label('Cancellation Time')
                ->default(now())
                ->required(),
        ];
    }
} 