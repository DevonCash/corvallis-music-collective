<?php

namespace CorvMC\Finance\Concerns;

use CorvMC\Finance\Models\Payment;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Auth;

trait HasPayments
{
    /**
     * Boot the HasPayments trait.
     */
    public static function bootHasPayments()
    {
        static::deleting(function ($model) {
            // Delete associated payments when the model is deleted
            $model->payments()->delete();
        });
    }

    /**
     * Get all payments for this model.
     */
    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    /**
     * Create a payment for this model.
     */
    public function createPayment(array $attributes = [])
    {
        if (!class_exists('CorvMC\Finance\Models\Payment')) {
            return null;
        }

        // Get the amount from attributes or model (in dollars)
        $amount = $attributes['amount'] ?? $this->total_price ?? 0;
        
        // Prepare payment data
        $paymentData = array_merge([
            'user_id' => $this->user_id ?? Auth::id(),
            'amount' => $amount, // Will be converted to cents by the Payment model
            'status' => 'pending',
            'description' => "Payment for " . class_basename($this),
            'due_date' => $this->start_time ?? now()->addDay(),
        ], $attributes);

        // Only set payable_id and payable_type if the model has been saved
        if ($this->exists) {
            $paymentData['payable_id'] = $this->getKey();
            $paymentData['payable_type'] = get_class($this);
        }

        // Create the payment
        $payment = Payment::create($paymentData);

        // If the payment is already marked as completed
        if ($payment->status === 'completed') {
            $this->handlePaymentCompleted($payment);
        }

        return $payment;
    }

    /**
     * Handle when a payment is completed.
     */
    public function handlePaymentCompleted(Payment $payment)
    {
        // Update the payment status of the model
        if (in_array('payment_status', $this->fillable)) {
            $this->update(['payment_status' => 'paid']);
        }
    }

    /**
     * Refund a payment for this model.
     */
    public function refund(array $attributes = [])
    {
        if (!class_exists('CorvMC\Finance\Models\Payment')) {
            return null;
        }

        // Get the amount from attributes or model (in dollars)
        $amount = $attributes['amount'] ?? $this->total_price ?? 0;
        
        // Prepare refund data
        $refundData = array_merge([
            'user_id' => $this->user_id ?? Auth::id(),
            'amount' => -1 * $amount, // Will be converted to negative cents by the Payment model
            'status' => 'completed',
            'description' => "Refund for " . class_basename($this),
            'payment_date' => now(),
            'payment_method' => 'refund',
        ], $attributes);

        // Only set payable_id and payable_type if the model has been saved
        if ($this->exists) {
            $refundData['payable_id'] = $this->getKey();
            $refundData['payable_type'] = get_class($this);
        }

        // Create the refund payment
        $refund = Payment::create($refundData);

        // Update the model's payment status
        if (in_array('payment_status', $this->fillable)) {
            $this->update(['payment_status' => 'refunded']);
        }

        return $refund;
    }
} 