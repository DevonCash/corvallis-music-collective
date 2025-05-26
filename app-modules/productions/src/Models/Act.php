<?php

namespace CorvMC\Productions\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Act extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'website',
        'social_links',
        'contact_info',
    ];

    protected $casts = [
        'social_links' => 'array',
        'contact_info' => 'array',
    ];

    public function productions(): BelongsToMany
    {
        return $this->belongsToMany(Production::class, 'production_act')
            ->withPivot(['order', 'set_length', 'notes'])
            ->withTimestamps();
    }
} 