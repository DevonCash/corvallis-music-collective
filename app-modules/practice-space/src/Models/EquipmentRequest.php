<?php

namespace CorvMC\PracticeSpace\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EquipmentRequest extends Model
{
    use HasFactory;

    protected $table = 'practice_space_equipment_requests';

    protected $fillable = [
        'booking_id',
        'equipment_name',
        'quantity',
        'notes',
        'status',
        'response_notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    /**
     * Get the booking that this equipment request is for.
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
} 