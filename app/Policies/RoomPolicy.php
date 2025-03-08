<?php

namespace App\Policies;

use App\Models\User;
use CorvMC\PracticeSpace\Models\Room;
use Illuminate\Auth\Access\HandlesAuthorization;

class RoomPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any rooms.
     */
    public function viewAny(User $user): bool
    {
        // Admin users can view all rooms
        // Non-admin users can only view active rooms (filtered in controller)
        return true;
    }

    public function manage(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can view the room.
     */
    public function view(User $user, Room $room): bool
    {
        // Admin users can view any room
        // Non-admin users can only view active rooms
        return $user->can('manage', Room::class) || $room->is_active;
    }

    /**
     * Determine whether the user can create rooms.
     */
    public function create(User $user): bool
    {
        // Only admins can create rooms
        return $user->can('manage', Room::class);
    }

    /**
     * Determine whether the user can update the room.
     */
    public function update(User $user, Room $room): bool
    {
        // Only admins can update rooms
        return $user->can('manage', Room::class);
    }

    /**
     * Determine whether the user can delete the room.
     */
    public function delete(User $user, Room $room): bool
    {
        // Only admins can delete rooms
        return $user->can('manage', Room::class);
    }

    /**
     * Determine whether the user can book the room.
     */
    public function book(User $user, Room $room): bool
    {
        // Admin users can book any room
        // Non-admin users can only book active rooms
        return $user->can('manage', Room::class) || $room->is_active;
    }

    /**
     * Determine whether the user can favorite the room.
     */
    public function favorite(User $user, Room $room): bool
    {
        // Admin users can favorite any room
        // Non-admin users can only favorite active rooms
        return $room->is_active;
    }

    /**
     * Determine whether the user can view room availability.
     */
    public function viewAvailability(User $user, Room $room): bool
    {
        // Admin users can view availability for any room
        // Non-admin users can only view availability for active rooms
        return $user->isAdmin() || $room->is_active;
    }
} 