<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\MediaLibrary\HasMedia;
use App\Traits\Publishable;
use Spatie\MediaLibrary\InteractsWithMedia;

class Event extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;
    use Publishable;

    protected $guarded = [];

    protected $casts = [
        "start_time" => "datetime",
        "end_time" => "datetime",
        "door_time" => "datetime",
        "links" => "array",
        "price" => "array",
        "tags" => "array",
    ];

    protected $appends = ["poster"];

    public function venue()
    {
        return $this->belongsTo(Venue::class);
    }

    public function bands()
    {
        return $this->belongsToMany(Band::class, "band_events");
    }

    public function mentions()
    {
        return $this->morphMany(Post::class, "mentionable");
    }

    public function children()
    {
        return $this->hasMany(Event::class, "parent_id");
    }

    public function getPosterAttribute()
    {
        return $this->getFirstMediaUrl("posters");
    }
}
