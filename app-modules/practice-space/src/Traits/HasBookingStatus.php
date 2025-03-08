<?php

namespace CorvMC\PracticeSpace\Traits;

use CorvMC\PracticeSpace\Enums\BookingStatus;
use CorvMC\PracticeSpace\Models\BookingStatusHistory;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Auth;

trait HasBookingStatus
{
    /**
     * Boot the trait.
     */
    public static function bootHasBookingStatus()
    {
        static::creating(function ($model) {
            if (!isset($model->status)) {
                $model->status = BookingStatus::SCHEDULED;
            }
        });

        static::created(function ($model) {
            // Record initial status
            $model->recordStatusChange($model->status, null, 'Initial status');
        });

        static::updating(function ($model) {
            // If status is changing, record it
            if ($model->isDirty('status')) {
                $model->recordStatusChange(
                    $model->status, 
                    $model->getOriginal('status'), 
                    request('status_note') ?? 'Status updated'
                );
            }
        });
    }

    /**
     * Get the status history for this booking.
     */
    public function statusHistory(): MorphMany
    {
        return $this->morphMany(BookingStatusHistory::class, 'model')
            ->orderBy('created_at', 'desc');
    }

    /**
     * Record a status change in the history.
     */
    public function recordStatusChange(BookingStatus $newStatus, ?BookingStatus $oldStatus = null, ?string $note = null): void
    {
        $this->statusHistory()->create([
            'status' => $newStatus,
            'previous_status' => $oldStatus,
            'note' => $note,
            'user_id' => Auth::id(),
        ]);
    }

    /**
     * Transition to a new status.
     */
    public function transitionTo(BookingStatus $status, ?string $note = null): self
    {
        $oldStatus = $this->status;
        $this->status = $status;
        $this->save();
        
        if ($note && $oldStatus !== $status) {
            $this->recordStatusChange($status, $oldStatus, $note);
        }
        
        return $this;
    }

    /**
     * Check if the booking is in a specific status.
     */
    public function hasStatus(BookingStatus $status): bool
    {
        return $this->status === $status;
    }

    /**
     * Check if the booking can transition to the given status.
     */
    public function canTransitionTo(BookingStatus $status): bool
    {
        // Define allowed transitions
        $allowedTransitions = [
            BookingStatus::SCHEDULED->value => [
                BookingStatus::CONFIRMED,
                BookingStatus::CANCELLED,
            ],
            BookingStatus::CONFIRMED->value => [
                BookingStatus::CHECKED_IN,
                BookingStatus::CANCELLED,
                BookingStatus::NO_SHOW,
            ],
            BookingStatus::CHECKED_IN->value => [
                BookingStatus::COMPLETED,
            ],
        ];

        // Get allowed transitions for current status
        $currentStatusTransitions = $allowedTransitions[$this->status->value] ?? [];
        
        return in_array($status, $currentStatusTransitions);
    }
} 