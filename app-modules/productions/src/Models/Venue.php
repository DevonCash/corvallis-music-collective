<?php

namespace CorvMC\Productions\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Venue extends Model
{
    protected $fillable = [
        'name',
        'address',
        'capacity',
        'description',
        'contact_info',
    ];

    protected $casts = [
        'address' => 'array',
        'contact_info' => 'array',
    ];

    public function productions(): HasMany
    {
        return $this->hasMany(Production::class);
    }
} 