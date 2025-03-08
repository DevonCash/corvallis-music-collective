<?php

namespace CorvMC\PracticeSpace\Http\Controllers;

use Illuminate\Routing\Controller;
use CorvMC\PracticeSpace\Models\Booking;
use CorvMC\PracticeSpace\Models\States\BookingState\CancelledState;
use CorvMC\PracticeSpace\Models\States\BookingState\ConfirmedState;
use CorvMC\PracticeSpace\Notifications\BookingCancellationNotification;
use CorvMC\PracticeSpace\Notifications\BookingUserConfirmedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BookingConfirmationController extends Controller
{
    /**
     * Confirm a booking.
     */
    public function confirm(Request $request, Booking $booking)
    {
        // Verify the URL signature
        if (!$request->hasValidSignature()) {
            abort(401, 'This confirmation link is invalid or has expired.');
        }
        
        // Check if the booking is already confirmed or cancelled
        if ($booking->state instanceof ConfirmedState) {
            return redirect()->route('practice-space.bookings.show', $booking)
                ->with('info', 'This booking is already confirmed.');
        }
        
        if ($booking->state instanceof CancelledState) {
            return redirect()->route('practice-space.bookings.show', $booking)
                ->with('error', 'This booking has been cancelled and cannot be confirmed.');
        }
        
        // Check if the confirmation deadline has passed
        if ($booking->confirmation_deadline && now()->isAfter($booking->confirmation_deadline)) {
            return redirect()->route('practice-space.bookings.show', $booking)
                ->with('error', 'The confirmation deadline has passed. Please contact us if you still wish to use this space.');
        }
        
        try {
            // Transition to confirmed state
            $booking->state = ConfirmedState::$name;
            
            // Update confirmation timestamp
            $booking->update([
                'confirmed_at' => now(),
            ]);
            
            // Send confirmation notification
            $user = $booking->user;
            $user->notify(new BookingUserConfirmedNotification($booking));
            
            // Log the notification in the activity log
            $booking->logNotificationSent(BookingUserConfirmedNotification::class, [
                'confirmed_at' => now(),
            ]);
            
            Log::info("Booking #{$booking->id} confirmed by user #{$user->id}");
            
            return redirect()->route('practice-space.bookings.show', $booking)
                ->with('success', 'Your booking has been confirmed. Thank you!');
        } catch (\Exception $e) {
            Log::error("Error confirming booking #{$booking->id}: {$e->getMessage()}", [
                'exception' => $e,
            ]);
            
            return redirect()->route('practice-space.bookings.show', $booking)
                ->with('error', 'There was an error confirming your booking. Please contact support.');
        }
    }
    
    /**
     * Cancel a booking.
     */
    public function cancel(Request $request, Booking $booking)
    {
        // Verify the URL signature
        if (!$request->hasValidSignature()) {
            abort(401, 'This cancellation link is invalid or has expired.');
        }
        
        // Check if the booking is already cancelled
        if ($booking->state instanceof CancelledState) {
            return redirect()->route('practice-space.bookings.show', $booking)
                ->with('info', 'This booking is already cancelled.');
        }
        
        try {
            // Transition to cancelled state
            $booking->state = CancelledState::$name;
            
            // Update cancellation reason
            $booking->update([
                'cancellation_reason' => 'Cancelled by user via email link',
                'cancelled_at' => now(),
            ]);
            
            // Send cancellation notification
            $user = $booking->user;
            $user->notify(new BookingCancellationNotification($booking));
            
            // Log the notification in the activity log
            $booking->logNotificationSent(BookingCancellationNotification::class, [
                'cancellation_reason' => 'Cancelled by user via email link',
                'cancelled_at' => now(),
            ]);
            
            Log::info("Booking #{$booking->id} cancelled by user #{$user->id}");
            
            return redirect()->route('practice-space.bookings.show', $booking)
                ->with('success', 'Your booking has been cancelled.');
        } catch (\Exception $e) {
            Log::error("Error cancelling booking #{$booking->id}: {$e->getMessage()}", [
                'exception' => $e,
            ]);
            
            return redirect()->route('practice-space.bookings.show', $booking)
                ->with('error', 'There was an error cancelling your booking. Please contact support.');
        }
    }
} 