<?php

namespace App\Modules\PracticeSpace\Models\States\BookingState;

/**
 * NoShow State
 *
 * Purpose: Represents a booking where the user failed to arrive for their scheduled practice session.
 * 
 * Entry Conditions:
 * - Transitions from Confirmed state when the user does not check in for their booking.
 * - This transition typically occurs after the scheduled end time has passed.
 * - The booking must have been previously confirmed but not checked in.
 * 
 * Exit Conditions:
 * - This is a terminal state - no further transitions are possible.
 * - NoShow bookings remain in this state for record-keeping, reporting, and potential no-show policy enforcement.
 */
class NoShow extends BookingState
{
    public static $name = 'no_show';
    public static string $color = 'danger';
}
