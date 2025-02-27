<?php

namespace App\Modules\PracticeSpace\Models;

use App\Modules\Payments\Models\Product;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
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

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
