<?php

namespace CorvMC\PracticeSpace\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoomEquipment extends Model
{
    use HasFactory;

    protected $table = 'practice_space_room_equipment';

    protected $fillable = [
        'room_id',
        'name',
        'description',
        'quantity',
        'condition',
        'last_maintenance_date',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'last_maintenance_date' => 'date',
    ];

    /**
     * Get the room that owns the equipment.
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }
} 