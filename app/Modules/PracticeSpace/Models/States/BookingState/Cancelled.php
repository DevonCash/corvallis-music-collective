<?php

namespace App\Modules\PracticeSpace\Models\States\BookingState;

/**
 * Cancelled State
 *
 * Purpose: Represents a booking that has been cancelled before it was utilized.
 * 
 * Entry Conditions:
 * - Can transition from either Scheduled or Confirmed states when a booking is explicitly cancelled.
 * - Cancellation may be initiated by the user, staff, or system based on business rules.
 * - No specific time constraints for cancellation are enforced in the state transition itself.
 * - When transitioning to this state, any pre-paid payments are automatically refunded.
 * 
 * Exit Conditions:
 * - This is a terminal state - no further transitions are possible.
 * - Cancelled bookings remain in this state for record-keeping and reporting purposes.
 * 
 * Special Behaviors:
 * - When a booking is cancelled, the ToCancelled transition will:
 *   1. Change the booking state to Cancelled
 *   2. Find all paid payments associated with this booking
 *   3. Transition each payment to Refunded state
 *   4. Process refunds through the payment processor (e.g., Stripe)
 */
class Cancelled extends BookingState
{
    public static $name = 'cancelled';
    public static string $color = 'danger';
}
