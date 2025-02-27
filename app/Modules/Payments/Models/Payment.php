<?php

namespace App\Modules\Payments\Models;

use App\Modules\Payments\Models\States\PaymentState\PaymentState;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'user_id',
        'product_id',
        'stripe_payment_intent_id',
        'method',
        'amount',
        'state',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'state' => PaymentState::class
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
