<?php

namespace App\Modules\PracticeSpace\Models\States\BookingState;

/**
 * CheckedIn State
 *
 * Purpose: Represents a booking where the user has arrived and checked in to the practice space.
 * 
 * Entry Conditions:
 * - Transitions from Confirmed state when the user physically arrives at the practice space.
 * - The check-in process typically involves verification by staff or an automated system.
 * - The booking must be in the Confirmed state before it can be checked in.
 * 
 * Exit Conditions:
 * - Can transition to Completed state when the practice session is finished and the user has left.
 * - This is typically the final active state before the booking is considered complete.
 */
class CheckedIn extends BookingState
{
    public static $name = 'checked_in';
    public static string $color = 'success';
}
