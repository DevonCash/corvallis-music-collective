<?php

namespace App\Policies;

use App\Models\User;
use CorvMC\PracticeSpace\Models\EquipmentRequest;
use Illuminate\Auth\Access\HandlesAuthorization;

class EquipmentRequestPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any equipment requests.
     */
    public function viewAny(User $user): bool
    {
        // Admin users can view all equipment requests
        // Non-admin users can only view their own (filtered in controller)
        return true;
    }

    /**
     * Determine whether the user can view the equipment request.
     */
    public function view(User $user, EquipmentRequest $equipmentRequest): bool
    {
        // Users can view their own equipment requests
        // Admin users can view any equipment request
        return $user->id === $equipmentRequest->booking->user_id || $user->isAdmin();
    }

    /**
     * Determine whether the user can create equipment requests.
     */
    public function create(User $user): bool
    {
        // Only admin users can create equipment requests
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can update the equipment request.
     */
    public function update(User $user, EquipmentRequest $equipmentRequest): bool
    {
        // Only admin users can update equipment requests
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the equipment request.
     */
    public function delete(User $user, EquipmentRequest $equipmentRequest): bool
    {
        // Only admin users can delete equipment requests
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can approve the equipment request.
     */
    public function approve(User $user, EquipmentRequest $equipmentRequest): bool
    {
        // Only admin users can approve equipment requests
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can reject the equipment request.
     */
    public function reject(User $user, EquipmentRequest $equipmentRequest): bool
    {
        // Only admin users can reject equipment requests
        return $user->isAdmin();
    }
} 