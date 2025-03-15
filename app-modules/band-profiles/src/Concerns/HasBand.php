<?php

namespace CorvMC\BandProfiles\Concerns;

use CorvMC\BandProfiles\Models\Band;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

trait HasBand
{
    public function bands(): BelongsToMany
    {
        return $this->belongsToMany(Band::class, 'band_members')
            ->withTimestamps()
            ->withPivot('role');
    }
} 