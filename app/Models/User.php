<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\MagicLoginLink;
use Spatie\Permission\Traits\HasRoles;
use App\Models\LoginToken;
use Illuminate\Database\Eloquent\Casts\Attribute;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = ["name", "email"];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            "email_verified_at" => "datetime",
        ];
    }

    protected function name()
    {
        return Attribute::make(
            get: fn(string $value) => $value ?? explode("@", $this->email)[0]
        );
    }

    public function loginTokens()
    {
        return $this->hasMany(LoginToken::class);
    }

    public function sendLoginLink()
    {
        // Create a new login token
        $plaintext = Str::random(32);
        $token = $this->loginTokens()->create([
            "token" => hash("sha256", $plaintext),
            "expires_at" => now()->addMinutes(15),
        ]);

        Mail::to($this->email)->sendNow(
            new MagicLoginLink($plaintext, $token->expires_at)
        );
    }
}
