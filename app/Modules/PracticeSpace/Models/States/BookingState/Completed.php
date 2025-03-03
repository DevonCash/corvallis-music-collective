<?php

namespace App\Modules\PracticeSpace\Models\States\BookingState;

/**
 * Completed State
 *
 * Purpose: Represents a booking that has been fully utilized and successfully completed.
 * 
 * Entry Conditions:
 * - Transitions from CheckedIn state after the practice session has concluded.
 * - The user must have previously checked in to the practice space.
 * - The practice session must have been completed as scheduled.
 * 
 * Exit Conditions:
 * - This is a terminal state - no further transitions are possible.
 * - Completed bookings remain in this state for record-keeping and reporting purposes.
 */
class Completed extends BookingState
{
    public static $name = 'completed';
    public static string $color = 'success';
}
