<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Storage;

class Event extends Model
{
    use HasFactory;

    protected $guarded = ["id", "created_at", "updated_at"];

    protected $casts = [
        "start_at" => "datetime",
        "end_at" => "datetime",
        "published_at" => "datetime",
    ];

    public static function published()
    {
        return Event::whereDate("published_at", "<", now());
    }

    public function bands()
    {
        return $this->belongsToMany(Band::class);
    }

    protected function image()
    {
        return Attribute::make(
            get: fn($value) => $value ? Storage::disk("s3")->url($value) : null,
            set: fn($value) => $value instanceof \Illuminate\Http\UploadedFile
                ? $value->store("events")
                : $value
        );
    }
}
