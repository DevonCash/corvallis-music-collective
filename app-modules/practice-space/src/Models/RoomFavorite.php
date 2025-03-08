<?php

namespace CorvMC\PracticeSpace\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoomFavorite extends Model
{
    use HasFactory;

    protected $table = 'practice_space_room_favorites';

    protected $fillable = [
        'user_id',
        'room_id',
        'notes',
    ];

    /**
     * Get the user that favorited the room.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the room that was favorited.
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }
} 