<?php

namespace CorvMC\Sponsorship\Concerns;

use CorvMC\Sponsorship\Models\Sponsor;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

trait HasSponsor
{
    public function sponsors(): BelongsToMany
    {
        return $this->belongsToMany(Sponsor::class, 'sponsor_users')
            ->withTimestamps()
            ->withPivot(['role'])
            ->wherePivotNotNull('role'); // Only return active sponsor relationships
    }
} 