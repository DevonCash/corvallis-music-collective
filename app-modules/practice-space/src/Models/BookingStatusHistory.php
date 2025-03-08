<?php

namespace CorvMC\PracticeSpace\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingStatusHistory extends Model
{
    use HasFactory;

    protected $table = 'practice_space_booking_status_histories';

    protected $fillable = [
        'booking_id',
        'from_status',
        'to_status',
        'user_id',
        'notes',
    ];

    /**
     * Get the booking that this status change belongs to.
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Get the user that made the status change.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
} 