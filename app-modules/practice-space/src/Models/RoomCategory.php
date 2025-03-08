<?php

namespace CorvMC\PracticeSpace\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use CorvMC\PracticeSpace\Database\Factories\RoomCategoryFactory;

class RoomCategory extends Model
{
    use HasFactory;

    protected $table = 'practice_space_room_categories';

    protected $fillable = [
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the rooms that belong to this category.
     */
    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    /**
     * Get the booking policy for this category.
     */
    public function bookingPolicy(): HasOne
    {
        return $this->hasOne(BookingPolicy::class);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return RoomCategoryFactory::new();
    }
} 