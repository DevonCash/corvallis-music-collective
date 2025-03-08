<?php

namespace CorvMC\PracticeSpace\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use CorvMC\PracticeSpace\Database\Factories\RoomFactory;

class Room extends Model
{
    use HasFactory;

    protected $table = 'practice_space_rooms';

    protected $fillable = [
        'room_category_id',
        'name',
        'description',
        'capacity',
        'hourly_rate',
        'is_active',
        'photos',
        'specifications',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'hourly_rate' => 'decimal:2',
        'is_active' => 'boolean',
        'photos' => 'array',
        'specifications' => 'array',
        'size_sqft' => 'integer',
        'amenities' => 'array',
    ];

    /**
     * Get the category that the room belongs to.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(RoomCategory::class, 'room_category_id');
    }

    /**
     * Get the bookings for the room.
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Get the equipment in the room.
     */
    public function equipment(): HasMany
    {
        return $this->hasMany(RoomEquipment::class);
    }

    /**
     * Get the maintenance schedules for the room.
     */
    public function maintenanceSchedules(): HasMany
    {
        return $this->hasMany(MaintenanceSchedule::class);
    }

    /**
     * Get the product associated with this room from the finance module.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product()
    {
        // This relationship will only work if the Finance module is installed
        if (class_exists('CorvMC\Finance\Models\Product')) {
            return $this->belongsTo('CorvMC\Finance\Models\Product');
        }
        
        // Return a null relationship if the Finance module is not installed
        return $this->belongsTo(self::class, 'id', 'id')->whereNull('id');
    }

    /**
     * Create or update the associated product in the finance module.
     * 
     * @param array $attributes Additional product attributes
     * @return mixed The product model or null if Finance module is not available
     */
    public function syncProduct(array $attributes = [])
    {
        if (!class_exists('CorvMC\Finance\Models\Product')) {
            return null;
        }

        $productClass = 'CorvMC\Finance\Models\Product';
        
        $productData = array_merge([
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->hourly_rate,
            'type' => 'service',
            'is_active' => $this->is_active,
        ], $attributes);

        if ($this->product_id) {
            // Update existing product
            $product = $productClass::find($this->product_id);
            if ($product) {
                $product->update($productData);
                return $product;
            }
        }
        
        // Create new product
        $product = $productClass::create($productData);
        $this->update(['product_id' => $product->id]);
        return $product;
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return RoomFactory::new();
    }
} 