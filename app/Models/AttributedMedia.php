<?php

namespace App\Models;

use Awcodes\Curator\Models\Media;

class AttributedMedia extends Media
{
    protected $table = "attributed_media";
    protected $fillable = ['path'];

    public function events()
    {
        return $this->hasMany(Event::class, "poster_id");
    }
}
