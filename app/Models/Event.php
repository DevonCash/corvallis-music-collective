<?php

namespace App\Models;

use Awcodes\Curator\Models\Media;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\Publishable;

class Event extends Model
{
    use HasFactory;
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

    public function poster()
    {
        return $this->belongsTo(Media::class, 'poster_id');
    }
}
