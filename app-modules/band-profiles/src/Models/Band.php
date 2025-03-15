<?php

namespace CorvMC\BandProfiles\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Band extends Model implements HasTenants
{
    use HasFactory;

    protected $fillable = [
        'name',
        'formation_date',
        'genre',
        'location',
        'bio',
    ];

    protected $casts = [
        'formation_date' => 'date',
    ];

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'band_members')
            ->withTimestamps()
            ->withPivot('role');
    }

    public function canAccessTenant(Model $tenant): bool
    {
        return $this->members()->where('user_id', Auth::id())->exists();
    }

    public function getTenants(Panel $panel): array|\Illuminate\Support\Collection
    {
        return $this->members()->where('user_id', Auth::id())->get();
    }

    protected static function newFactory()
    {
        return \CorvMC\BandProfiles\Database\Factories\BandFactory::new();
    }
} 