<?php

namespace CorvMC\PracticeSpace\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceSchedule extends Model
{
    use HasFactory;

    protected $table = 'practice_space_maintenance_schedules';

    protected $fillable = [
        'room_id',
        'title',
        'description',
        'start_time',
        'end_time',
        'status',
        'technician_name',
        'technician_contact',
        'notes',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    /**
     * Get the room that is being maintained.
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }
} 