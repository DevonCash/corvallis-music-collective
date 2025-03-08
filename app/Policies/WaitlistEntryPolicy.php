<?php

namespace App\Policies;

use App\Models\User;
use CorvMC\PracticeSpace\Models\WaitlistEntry;
use Illuminate\Auth\Access\HandlesAuthorization;

class WaitlistEntryPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any waitlist entries.
     */
    public function viewAny(User $user): bool
    {
        // Admin users can view all waitlist entries
        // Non-admin users can only view their own (filtered in controller)
        return true;
    }

    /**
     * Determine whether the user can view the waitlist entry.
     */
    public function view(User $user, WaitlistEntry $waitlistEntry): bool
    {
        // Users can view their own waitlist entries
        // Admin users can view any waitlist entry
        return $user->id === $waitlistEntry->user_id || $user->isAdmin();
    }

    /**
     * Determine whether the user can create waitlist entries.
     */
    public function create(User $user): bool
    {
        // Only admin users can create waitlist entries
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can update the waitlist entry.
     */
    public function update(User $user, WaitlistEntry $waitlistEntry): bool
    {
        // Only admin users can update waitlist entries
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the waitlist entry.
     */
    public function delete(User $user, WaitlistEntry $waitlistEntry): bool
    {
        // Only admin users can delete waitlist entries
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can convert the waitlist entry to a booking.
     */
    public function convertToBooking(User $user, WaitlistEntry $waitlistEntry): bool
    {
        // Only admin users can convert waitlist entries to bookings
        return $user->isAdmin();
    }
} 