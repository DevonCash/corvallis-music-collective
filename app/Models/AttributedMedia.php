<?php

namespace App\Models;

use Awcodes\Curator\Models\Media;

class AttributedMedia extends Media
{
    protected $table = "attributed_media";
    protected $fillable = ['path'];


    protected static function boot(){
        parent::boot();
        static::creating( function ($model) {
            if (empty($model->name)) {
                $model->name = $model->file_name;
            }
        });
    }

    public function events()
    {
        return $this->hasMany(Event::class, "poster_id");
    }
}
