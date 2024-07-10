<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\Publishable;

class Band extends Model
{
    use HasFactory;
    use Publishable;
    use SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            "links" => "array",
            "tags" => "array",
        ];
    }

    public function members()
    {
        return $this->belongsToMany(User::class, "user_bands");
    }

    public function events()
    {
        return $this->belongsToMany(Event::class, "band_events");
    }

    public function mentions()
    {
        return $this->morphMany(Post::class, "mentionable");
    }
}
