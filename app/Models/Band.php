<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Band extends Model
{
    use HasFactory;

    protected $guarded = ["id", "created_at", "updated_at", "deleted_at"];

    protected $casts = [
        "published_at" => "datetime",
        "links" => "array",
    ];

    public function events()
    {
        return $this->belongsToMany(Event::class);
    }
}
