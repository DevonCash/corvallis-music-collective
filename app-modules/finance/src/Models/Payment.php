<?php

namespace CorvMC\Finance\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use CorvMC\Finance\Database\Factories\PaymentFactory;

class Payment extends Model
{
    use HasFactory;

    protected $table = 'finance_payments';

    protected $fillable = [
        'user_id',
        'amount',
        'status',
        'description',
        'due_date',
        'payment_date',
        'payment_method',
        'transaction_id',
        'payable_id',
        'payable_type',
    ];

    protected $casts = [
        'amount' => 'integer',
        'due_date' => 'datetime',
        'payment_date' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'amount_dollars',
    ];

    /**
     * Get the user that made the payment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the parent payable model.
     */
    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Mark the payment as completed.
     */
    public function markAsCompleted(): self
    {
        $this->update([
            'status' => 'completed',
            'payment_date' => now(),
        ]);

        // If the payable model has a method to handle payment completion
        if (method_exists($this->payable, 'handlePaymentCompleted')) {
            $this->payable->handlePaymentCompleted($this);
        }

        return $this;
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return PaymentFactory::new();
    }

    /**
     * Get the amount in dollars (decimal).
     * This is the default way to access the amount.
     */
    public function getAmountDollarsAttribute()
    {
        return $this->amount / 100;
    }

    /**
     * Set the amount from dollars to cents.
     */
    public function setAmountDollarsAttribute($value)
    {
        $this->attributes['amount'] = (int) round($value * 100);
    }

    /**
     * Get the amount in cents (for Stripe).
     * This is already the raw value in the database.
     */
    public function getAmountCentsAttribute()
    {
        return $this->amount;
    }

    /**
     * Get the formatted amount with currency symbol.
     */
    public function getFormattedAmountAttribute()
    {
        return '$' . number_format($this->amount_dollars, 2);
    }

    /**
     * Mutator for the amount attribute to handle both dollars and cents.
     * If the value is a float or has a decimal point, treat it as dollars.
     * Otherwise, treat it as cents.
     */
    public function setAmountAttribute($value)
    {
        // If it's a float or has a decimal point, treat as dollars
        if (is_float($value) || (is_string($value) && strpos($value, '.') !== false)) {
            $this->attributes['amount'] = (int) round($value * 100);
        } else {
            // Otherwise treat as cents
            $this->attributes['amount'] = (int) $value;
        }
    }

    /**
     * Get the amount attribute.
     * By default, return the amount in dollars for easier use in the codebase.
     */
    public function getAmountAttribute($value)
    {
        // Return dollars by default
        return $value / 100;
    }
} 