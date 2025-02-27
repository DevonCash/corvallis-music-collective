<?php

namespace App\Modules\Payments\Models;

use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name',
        'description',
        'prices',
        'stripe_product_id',
        'is_visible',
        'subscription_interval'
    ];

    protected $casts = [
        'is_visible' => 'boolean',
        'prices' => 'array',
    ];

    public function getPaymentMode(): string
    {
        return $this->is_subscription ? 'subscription' : 'payment';
    }

    public function createCheckoutSession(User $user, array $options = [])
    {
        return $user->checkout(array_merge([
            'line_items' => [
                [
                    'price' => $this->stripe_price_id,
                    'quantity' => 1
                ],
            ],
            'mode' => 'payment',
            'success_url' => route('payments.success', ['product' => $this->id]),
            'cancel_url' => route('payments.cancel', ['product' => $this->id]),
            'metadata' => [
                'cmc_product_id' => $this->id,
                'cmc_user_id' => $user->id
            ]
        ], $options));
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
