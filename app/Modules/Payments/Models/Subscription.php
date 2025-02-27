<?php

namespace App\Modules\Payments\Models;

use App\Modules\Payments\Models\States\SubscriptionState\SubscriptionState;
use App\Modules\Payments\Concerns\HasPayments;
use App\Modules\Payments\Contracts\Payable;
use Illuminate\Database\Eloquent\Model;
use Spatie\ModelStates\HasStates;

class Subscription extends Model implements Payable {
    use HasPayments, HasStates;

    protected $fillable = [
        'user_id',
        'product_id',
        'stripe_subscription_id',
        'stripe_price_id',
        'status',
    ];

    protected $casts = [
        'status' => SubscriptionState::class
    ];
}
