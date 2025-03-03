<?php

namespace App\Modules\Payments\Concerns;

use App\Modules\Payments\Contracts\Payable;
use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Models\Product;
use App\Modules\Payments\Models\States\PaymentState;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Trait HasPayments
 * 
 * This trait provides a full implementation of the Payable interface.
 * It adds polymorphic payment relationship and payment-related functionality
 * to any model that needs to accept payments.
 * 
 * By using this trait, the model will implement all the methods required
 * by the Payable interface.
 */
trait HasPayments
{
    /**
     * Get all payments for this model
     * 
     * @return MorphMany
     */
    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    /**
     * Get the total amount that has been paid
     * 
     * @return float
     */
    public function getTotalPaidAmount(): float
    {
        return $this->payments()
            ->where('state', PaymentState\Paid::$name)
            ->sum('amount');
    }

    /**
     * Get the amount that is still owed
     * 
     * @return float
     */
    public function getAmountOwed(): float
    {
        return $this->getPayableAmount() - $this->getTotalPaidAmount();
    }

    /**
     * Check if this model is fully paid
     * 
     * @return bool
     */
    public function isPaid(): bool
    {
        return $this->getAmountOwed() <= 0;
    }

    /**
     * Get the total payable amount
     * 
     * This method can be overridden by the implementing class to provide
     * custom payment amount logic.
     * 
     * @return float
     * @throws \RuntimeException if amount property is not available
     */
    public function getPayableAmount(): float
    {
        // Option 1: The model has a direct amount property
        if (isset($this->amount)) {
            return $this->amount;
        }
        
        // Option 2: The model implements a calculateAmount method
        if (method_exists($this, 'calculateAmount')) {
            return $this->calculateAmount();
        }
        
        throw new \RuntimeException(
            'Model ' . get_class($this) . ' must either have an "amount" property or ' .
            'implement a "calculateAmount()" method'
        );
    }

    /**
     * Get a human-readable description for payment purposes
     * 
     * @return string
     */
    public function getPayableDescription(): string
    {
        // Allow model to customize its description
        if (method_exists($this, 'getPaymentDescription')) {
            return $this->getPaymentDescription();
        }
        
        return class_basename($this) . " #{$this->id}";
    }

    /**
     * Get the user associated with this payment
     * 
     * @return User
     * @throws \RuntimeException if user relation is not available
     */
    public function getUser(): User
    {
        if (!method_exists($this, 'user')) {
            throw new \RuntimeException(sprintf(
                'Model %s must have a user() relationship or override getUser()',
                get_class($this)
            ));
        }

        return $this->user;
    }

    /**
     * Get the product associated with this payment
     * 
     * @return Product
     * @throws \RuntimeException if product relation is not available
     */
    public function getProduct(): Product
    {
        // If model has a direct product relationship, use it
        if (method_exists($this, 'product')) {
            return $this->product;
        }
        
        // If model has a getPaymentProduct method, use it
        if (method_exists($this, 'getPaymentProduct')) {
            return $this->getPaymentProduct();
        }

        throw new \RuntimeException(sprintf(
            'Model %s must have a product() relationship or implement getPaymentProduct()',
            get_class($this)
        ));
    }

    /**
     * Create a new payment for this model
     * 
     * @param array $attributes Payment attributes
     * @return Payment
     */
    public function createPayment(array $attributes): Payment
    {
        return $this->payments()->create($attributes);
    }

    /**
     * Get line items for checkout
     * 
     * This method can be overridden for more complex line item requirements
     * 
     * @return array
     */
    public function getLineItems(): array
    {
        // Allow model to provide custom line items
        if (method_exists($this, 'getPaymentLineItems')) {
            return $this->getPaymentLineItems();
        }
        
        return [
            [
                'price' => $this->getProduct()->stripe_price_id,
                'quantity' => 1,
            ]
        ];
    }

    /**
     * Get checkout options for payment processing
     * 
     * @return array
     */
    public function getCheckoutOptions(): array
    {
        $modelName = strtolower(class_basename($this));

        return [
            'mode' => $this->getProduct()->getPaymentMode(),
            'success_url' => route("{$modelName}s.payment.success", [$modelName => $this->id]),
            'cancel_url' => route("{$modelName}s.payment.cancel", [$modelName => $this->id]),
            'metadata' => [
                'payable_type' => get_class($this),
                'payable_id' => $this->id,
                'product_id' => $this->getProduct()->id,
                'user_id' => $this->getUser()->id
            ]
        ];
    }
}
