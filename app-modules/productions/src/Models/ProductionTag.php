<?php

namespace CorvMC\Productions\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ProductionTag extends Model
{
    protected $fillable = [
        'name',
        'type',
    ];

    public function productions(): BelongsToMany
    {
        return $this->belongsToMany(Production::class, 'production_tag');
    }
} 