<?php

namespace App\Modules\Payments\Contracts;

use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Models\Product;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Payable Interface
 *
 * This interface defines the contract for models that can receive payments.
 * Any model that implements this interface should be able to have payments 
 * associated with it, calculate amounts owed, and handle payment processing.
 *
 * This interface is fully implemented by the HasPayments trait.
 */
interface Payable
{
    /**
     * Get all payments for this model
     */
    public function payments(): MorphMany;
    
    /**
     * Get the total amount that has been paid
     */
    public function getTotalPaidAmount(): float;
    
    /**
     * Get the amount that is still owed
     */
    public function getAmountOwed(): float;
    
    /**
     * Check if this model is fully paid
     */
    public function isPaid(): bool;
    
    /**
     * Get the total payable amount
     */
    public function getPayableAmount(): float;
    
    /**
     * Get a human-readable description for payment purposes
     */
    public function getPayableDescription(): string;
    
    /**
     * Get the user associated with this payment
     */
    public function getUser(): User;
    
    /**
     * Get the product associated with this payment
     */
    public function getProduct(): Product;
    
    /**
     * Create a new payment for this model
     */
    public function createPayment(array $attributes): Payment;
    
    /**
     * Get line items for checkout
     */
    public function getLineItems(): array;
    
    /**
     * Get checkout options for payment processing
     */
    public function getCheckoutOptions(): array;
}
