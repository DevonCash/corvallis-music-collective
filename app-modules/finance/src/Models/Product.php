<?php

namespace CorvMC\Finance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use CorvMC\Finance\Database\Factories\ProductFactory;

class Product extends Model
{
    use HasFactory;

    protected $table = 'finance_products';

    protected $fillable = [
        'name',
        'description',
        'price',
        'is_active',
        'productable_id',
        'productable_type',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Get the parent productable model.
     */
    public function productable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return ProductFactory::new();
    }
} 