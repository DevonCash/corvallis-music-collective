<?php

namespace App\Modules\PracticeSpace\Models\States\BookingState;

/**
 * Scheduled State
 *
 * Purpose: Represents a booking that has been initially created and scheduled but not yet confirmed.
 * 
 * Entry Conditions:
 * - This is the default initial state for all new bookings.
 * - A booking is automatically created in this state when a user schedules a practice space.
 * 
 * Exit Conditions:
 * - Can transition to Confirmed state when the booking is within 3 days of the start time.
 * - Can transition to Cancelled state at any time before the booking occurs.
 */
class Scheduled extends BookingState
{
    public static $name = 'scheduled';

    public static string $color = 'primary';
}
