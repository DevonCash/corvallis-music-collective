<?php

namespace CorvMC\PracticeSpace\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaitlistEntry extends Model
{
    use HasFactory;

    protected $table = 'practice_space_waitlist_entries';

    protected $fillable = [
        'user_id',
        'room_id',
        'preferred_date',
        'preferred_start_time',
        'preferred_end_time',
        'is_flexible',
        'notes',
        'status',
        'notification_sent_at',
    ];

    protected $casts = [
        'preferred_date' => 'date',
        'preferred_start_time' => 'datetime',
        'preferred_end_time' => 'datetime',
        'is_flexible' => 'boolean',
        'notification_sent_at' => 'datetime',
    ];

    /**
     * Get the user that is on the waitlist.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the room that the user is waiting for.
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }
} 