<?php

namespace App\Modules\PracticeSpace\Models;

use App\Modules\Payments\Concerns\HasPayments;
use App\Modules\Payments\Models\Product;
use App\Modules\PracticeSpace\Models\States\BookingState\BookingState;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Booking extends Model
{
    use HasPayments, LogsActivity;
    protected $fillable = [
        'user_id',
        'room_id',
        'payment_id',
        'start_time',
        'end_time',
        'state'
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'state' => BookingState::class
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function paymentStatus(): string
    {
        if($this->getAmountOwed() <= 0) {
            return 'paid';
        }

        return 'unpaid';
    }

    public function getProduct(): Product
    {
        return $this->room->product;
    }

    public function getDurationAttribute(): int
    {
        return $this->start_time->diffInHours($this->end_time);
    }

    public function getPrice(): int
    {
        return $this->getProduct()->price ?? 0;
    }

    public function getPayableAmount(): int
    {
        $hours = $this->start_time->diffInHours($this->end_time);
        return floor($this->getProduct()->price * $hours);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['state']);
    }
}
