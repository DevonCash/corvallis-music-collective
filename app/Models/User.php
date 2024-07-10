<?php

namespace App\Models;

use App\Traits\Publishable;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory;
    use Publishable;
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = ["name", "email", "password"];

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

    function getUserName()
    {
        return $this->name ?? explode("@", $this->email)[0];
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

    public function canAccessFilament()
    {
        return true;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() == "admin") {
            return $this->hasVerifiedEmail() &&
                str_ends_with($this->email, "@corvmc.org");
        }
    }
}
