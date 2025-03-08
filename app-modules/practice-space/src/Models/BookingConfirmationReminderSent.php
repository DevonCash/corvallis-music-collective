<?php

namespace CorvMC\PracticeSpace\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingConfirmationReminderSent extends Model
{
    protected $table = 'practice_space_booking_confirmation_reminders_sent';
    
    protected $fillable = [
        'booking_id',
        'hours_before_deadline',
        'sent_at',
    ];
    
    protected $casts = [
        'sent_at' => 'datetime',
    ];
    
    /**
     * Get the booking that this confirmation reminder was sent for.
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
} 