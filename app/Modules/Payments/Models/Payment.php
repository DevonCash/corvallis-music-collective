<?php

namespace App\Modules\Payments\Models;

use App\Modules\Payments\Models\States\PaymentState\PaymentState;
use App\Modules\User\Models\User;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_id',
        'stripe_payment_intent_id',
        'method',
        'amount',
        'state',
        'payable_type',
        'payable_id',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'state' => PaymentState::class
    ];

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return PaymentFactory::new();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the parent payable model (e.g. booking, subscription, etc).
     */
    public function payable(): MorphTo
    {
        return $this->morphTo();
    }
}
