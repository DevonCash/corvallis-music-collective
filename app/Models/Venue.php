<?php

namespace App\Models;

use App\Traits\Publishable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Venue extends Model
{
    use HasFactory;
    use Publishable;

    protected $guarded = [];

    protected $casts = [
        "location" => "object",
    ];

    public function events()
    {
        return $this->hasMany(Event::class);
    }

    public function mentions()
    {
        return $this->morphMany(Post::class, "mentionable");
    }
}
