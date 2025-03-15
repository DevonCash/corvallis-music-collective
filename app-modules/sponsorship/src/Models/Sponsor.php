<?php

namespace CorvMC\Sponsorship\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Sponsor extends Model implements HasTenants
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type', // 'business_sponsor' or 'community_partner'
        'contact_email',
        'contact_phone',
        'website',
        'description',
        'tier_id',
        'active_until',
    ];

    protected $casts = [
        'active_until' => 'date',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'sponsor_users')
            ->withTimestamps()
            ->withPivot('role');
    }

    public function canAccessTenant(Model $tenant): bool
    {
        return $this->users()->where('user_id', Auth::id())->exists();
    }

    public function getTenants(Panel $panel): array|\Illuminate\Support\Collection
    {
        return $this->users()->where('user_id', Auth::id())->get();
    }

    protected static function newFactory()
    {
        return \CorvMC\Sponsorship\Database\Factories\SponsorFactory::new();
    }
} 