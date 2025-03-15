<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Traits\HasRoles;
use Lab404\Impersonate\Models\Impersonate;
use Illuminate\Support\Str;
use Laravel\Cashier\Billable;
use CorvMC\BandProfiles\Concerns\HasBand;
use CorvMC\Sponsorship\Concerns\HasSponsor;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use CorvMC\BandProfiles\Models\Band;
use CorvMC\Sponsorship\Models\Sponsor;

class User extends Authenticatable implements FilamentUser, HasTenants
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles, Impersonate, Billable, HasBand, HasSponsor;

    public static string $factory = \Database\Factories\UserFactory::class;
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function canAccessPanel(Panel $panel): bool
    {
        // Member panel is accessible to all authenticated users
        if ($panel->getId() === 'member') {
            return true;
        }

        // Admin panel requires admin role
        if ($panel->getId() === 'admin') {
            return $this->hasRole('admin');
        }

        // Band panel requires at least one band membership
        if ($panel->getId() === 'band') {
            return $this->bands()->exists();
        }

        // Sponsor panel requires at least one active sponsor relationship
        if ($panel->getId() === 'sponsor') {
            return $this->sponsors()->exists();
        }

        return false;
    }

    public function getTenants(Panel $panel): array|\Illuminate\Support\Collection
    {
        return match ($panel->getId()) {
            'band' => $this->bands,
            'sponsor' => $this->sponsors,
            default => collect(),
        };
    }

    public function canAccessTenant($tenant): bool
    {
        if (!$tenant instanceof Model) {
            return false;
        }

        // For band tenants
        if ($tenant instanceof \CorvMC\BandProfiles\Models\Band) {
            return $this->bands()->where('band_id', $tenant->id)->exists();
        }

        // For sponsor tenants
        if ($tenant instanceof \CorvMC\Sponsorship\Models\Sponsor) {
            return $this->sponsors()->where('sponsor_id', $tenant->id)->exists();
        }

        return false;
    }

    public function bands(): BelongsToMany
    {
        return $this->belongsToMany(Band::class, 'band_members')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function sponsors(): BelongsToMany
    {
        return $this->belongsToMany(Sponsor::class, 'sponsor_users')
            ->withPivot('role')
            ->withTimestamps();
    }
}
