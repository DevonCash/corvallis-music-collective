<?php

namespace App\Models;

use App\Traits\Publishable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Filament\Models\Contracts\FilamentUser;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory;
    use Notifiable;
    use Publishable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = ["email", "password", "name"];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = ["password", "remember_token"];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            "email_verified_at" => "datetime",
            "password" => "hashed",
            "links" => "array",
        ];
    }

    public function canAccessPanel(\Filament\Panel $panel): bool
    {
        if ($panel->getId() == "admin") {
            return $this->hasVerifiedEmail() &&
                str_ends_with($this->email, "@corvmc.org");
        }
        return false;
    }

    /**
     * Get the bands.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function bands()
    {
        return $this->belongsToMany(Band::class, "user_bands");
    }

    /**
     * Get the posts.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function posts()
    {
        return $this->belongsToMany(Post::class);
    }

    /**
     * Get the memberships.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function memberships()
    {
        return $this->hasOne(Membership::class, "user_id");
    }
}
