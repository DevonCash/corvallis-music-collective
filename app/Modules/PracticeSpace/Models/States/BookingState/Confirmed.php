<?php

namespace App\Modules\PracticeSpace\Models\States\BookingState;

/**
 * Confirmed State
 *
 * Purpose: Represents a booking that has been confirmed by the system or administrator.
 * 
 * Entry Conditions:
 * - Transitions from Scheduled state when the booking is within 3 days of the start time.
 * - Requires that the booking start time is less than 3 days away (booking->start_time->subDays(3)->startOfDay()->isPast()).
 * 
 * Exit Conditions:
 * - Can transition to CheckedIn state when the user arrives at the practice space.
 * - Can transition to Cancelled state if the booking is cancelled before the session.
 * - Can transition to NoShow state if the user doesn't show up for their booking.
 */
class Confirmed extends BookingState
{
    public static $name = 'confirmed';
    public static string $color = 'success';
}
