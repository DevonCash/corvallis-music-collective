<?php

namespace App\Modules\PracticeSpace\Models;

use App\Modules\Payments\Concerns\HasPayments;
use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Models\Product;
use App\Modules\PracticeSpace\Models\States\BookingState\BookingState;
use App\Modules\User\Models\User;
use Database\Factories\BookingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Booking extends Model
{
    use HasPayments, LogsActivity, HasFactory;
    
    protected $fillable = [
        'user_id',
        'room_id',
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
    
    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return BookingFactory::new();
    }
    
    /**
     * Get the user associated with this booking
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the room associated with this booking
     */
    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Get all payments associated with this booking via polymorphic relationship
     */
    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    /**
     * Get the product for payment processing
     * Implementation of getPaymentProduct() for HasPayments trait
     */
    public function getPaymentProduct(): Product
    {
        return $this->room->product;
    }

    /**
     * Get the duration of the booking in hours
     */
    public function getDurationAttribute(): int
    {
        return $this->start_time->diffInHours($this->end_time);
    }

    /**
     * Get the hourly price for this booking
     */
    public function getPrice(): int
    {
        return $this->getPaymentProduct()->prices['hourly']['amount'] ?? 0;
    }

    /**
     * Calculate the total payable amount
     * Implementation for HasPayments trait
     */
    public function calculateAmount(): int
    {
        $hours = $this->duration;
        return floor($this->getPrice() * $hours);
    }

    /**
     * Get a description for payment records
     * Implementation for HasPayments trait
     */
    public function getPaymentDescription(): string
    {
        $roomName = $this->room->name ?? 'Room';
        $startTime = $this->start_time->format('M j, Y g:i A');
        
        return "Booking for {$roomName} on {$startTime}";
    }

    /**
     * Get custom line items for payment checkout
     * Implementation for HasPayments trait
     */
    public function getPaymentLineItems(): array
    {
        return [
            [
                'price' => $this->getPaymentProduct()->stripe_price_id ?? null,
                'quantity' => $this->duration,
                'product_data' => [
                    'name' => $this->room->name ?? 'Room Booking',
                    'description' => "Booking from {$this->start_time->format('g:i A')} to {$this->end_time->format('g:i A')} on {$this->start_time->format('M j, Y')}"
                ]
            ]
        ];
    }

    /**
     * Configure which attributes to log
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['state']);
    }
}
