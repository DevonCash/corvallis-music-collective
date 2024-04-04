<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}
