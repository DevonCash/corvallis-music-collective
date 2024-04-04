<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    use HasFactory;

    protected $fillable = ["name", "route", "template", "localization"];

    protected function casts(): array
    {
        return [
            "localization" => "json",
        ];
    }
}
