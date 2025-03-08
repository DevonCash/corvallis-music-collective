<?php

namespace CorvMC\StateManagement\Example;

use Illuminate\Database\Eloquent\Model;

/**
 * This is an example model that uses state casting.
 */
class BookingModel extends Model
{
    protected $table = 'bookings';
    
    protected $fillable = [
        'user_id',
        'room_id',
        'start_time',
        'end_time',
        'state',
        'scheduled_at',
        'confirmed_at',
        'checked_in_at',
    ];
    
    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'scheduled_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'checked_in_at' => 'datetime',
        'state' => BookingState::class,
    ];
} 