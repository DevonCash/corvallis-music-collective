<?php

namespace CorvMC\PracticeSpace\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use CorvMC\PracticeSpace\Database\Factories\RoomCategoryFactory;
use CorvMC\PracticeSpace\ValueObjects\BookingPolicy;

class RoomCategory extends Model
{
    use HasFactory;

    protected $table = 'practice_space_room_categories';

    protected $fillable = [
        'name',
        'description',
        'is_active',
        'default_booking_policy',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'default_booking_policy' => BookingPolicy::class,
    ];

    /**
     * Get the rooms that belong to this category.
     */
    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class, 'room_category_id');
    }

    /**
     * Set the default booking policy for this category
     * 
     * @param BookingPolicy|array|null $value
     * @return void
     */
    public function setDefaultBookingPolicyAttribute($value): void
    {
        // If null is provided, use a default policy
        if ($value === null) {
            $this->attributes['default_booking_policy'] = json_encode(new BookingPolicy());
            return;
        }
        
        // If an array is provided, convert it to a BookingPolicy instance
        if (is_array($value)) {
            $value = BookingPolicy::fromArray($value);
        }
        
        // Ensure the value is a BookingPolicy instance
        if (!$value instanceof BookingPolicy) {
            throw new \InvalidArgumentException('The default booking policy must be a BookingPolicy instance, an array, or null.');
        }
        
        // Store the policy as JSON
        $this->attributes['default_booking_policy'] = json_encode($value);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return RoomCategoryFactory::new();
    }
} 