<?php

namespace App\Policies;

use App\Models\User;
use CorvMC\PracticeSpace\Models\RoomCategory;
use Illuminate\Auth\Access\HandlesAuthorization;

class RoomCategoryPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any room categories.
     */
    public function viewAny(User $user): bool
    {
        // All users can view room categories (needed for booking UI)
        return true;
    }


    public function manage(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can view the room category.
     */
    public function view(User $user, RoomCategory $roomCategory): bool
    {
        // All users can view room category details (needed for booking UI)
        return true;
    }

    /**
     * Determine whether the user can create room categories.
     */
    public function create(User $user): bool
    {
        // Only admins can create room categories
        return $user->can('manage', RoomCategory::class);
    }

    /**
     * Determine whether the user can update the room category.
     */
    public function update(User $user, RoomCategory $roomCategory): bool
    {
        // Only admins can update room categories
        return $user->can('manage', RoomCategory::class);
    }

    /**
     * Determine whether the user can delete the room category.
     */
    public function delete(User $user, RoomCategory $roomCategory): bool
    {
        // Only admins can delete room categories
        // Additionally, check if there are no rooms in this category
        return $user->can('manage', RoomCategory::class) && $roomCategory->rooms()->count() === 0;
    }
} 