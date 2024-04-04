<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory;

    protected $guarded = ["id", "created_at", "updated_at"];

    protected function casts(): array
    {
        return [
            "published_at" => "datetime",
        ];
    }

    public static function published()
    {
        return static::whereDate("published_at", "<", now());
    }
}
