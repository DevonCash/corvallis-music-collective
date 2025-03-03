<?php

namespace App\Modules\PracticeSpace\Models;

use App\Modules\Payments\Models\Product;
use Database\Factories\RoomFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'product_id',
        'description',
        'capacity',
        'amenities',
        'hours'
    ];

    protected $casts = [
        'amenities' => 'array',
        'hours' => 'array'
    ];

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return RoomFactory::new();
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
