<?php

namespace App\Policies;

use App\Models\User;
use CorvMC\PracticeSpace\Models\Booking;
use Illuminate\Auth\Access\HandlesAuthorization;

class BookingPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any bookings.
     */
    public function viewAny(User $user): bool
    {
        // Admin users can view all bookings
        // Non-admin users can only view their own bookings (filtered in controller)
        return true;
    }

    /**
     * Determine whether the user can manage bookings.
     */
    public function manage(User $user): bool
    {
      return $user->isAdmin();
    }

    /**
     * Determine whether the user can view the booking.
     */
    public function view(User $user, Booking $booking): bool
    {
        // Users can view their own bookings or admins can view any booking
        return $user->id === $booking->user_id || $user->isAdmin();
    }

    /**
     * Determine whether the user can create bookings.
     */
    public function create(User $user): bool
    {
      return true;
    }

    /**
     * Determine whether the user can update the booking.
     */
    public function update(User $user, Booking $booking): bool
    {
        return $user->id === $booking->user_id ?? $user->can('manage', Booking::class);
    }

    /**
     * Determine whether the user can delete the booking.
     */
    public function delete(User $user, Booking $booking): bool
    {
        // Only admins can delete bookings
        return $user->can('manage', Booking::class);
    }

    /**
     * Determine whether the user can cancel the booking.
     */
    public function cancel(User $user, Booking $booking): bool
    {
        // Users can cancel their own bookings, or admins can cancel any booking
        return $user->id === $booking->user_id || $user->can('manage', Booking::class);
    }

    public function confirm(User $user, Booking $booking): bool
    {
        // Users can confirm their own bookings, or admins can confirm any booking
        return $user->id === $booking->user_id || $user->can('manage',Booking::class);
    }

    /**
     * Determine whether the user can check in for the booking.
     */
    public function checkIn(User $user, Booking $booking): bool
    {
        // Only admins can check in bookings
        return $user->can('manage', Booking::class);
    }

    /**
     * Determine whether the user can check out from the booking.
     */
    public function checkOut(User $user, Booking $booking): bool
    {
        // Only admins can check out bookings
        return $user->can('manage', Booking::class);
    }

    /**
     * Determine whether the user can mark a booking as no-show.
     */
    public function markNoShow(User $user, Booking $booking): bool
    {
        // Only admins can mark bookings as no-show
        return $user->can('manage', Booking::class);
    }
} 