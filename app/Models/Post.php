<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;
use App\Traits\Publishable;

class Post extends Model
{
    use HasFactory;
    use Publishable;

    protected $guarded = [];

    protected $casts = [
        "tags" => "array",
    ];

    protected $appends = ["excerpt", "url"];

    public function getUrlAttribute()
    {
        return route("posts.show", $this);
    }

    public function getExcerptAttribute()
    {
        return Str::words($this->content, 30, "...");
    }

    public function authors()
    {
        return $this->belongsToMany(User::class);
    }

    public function mention()
    {
        return $this->morphTo();
    }
}
