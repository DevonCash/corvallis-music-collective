<?php

namespace App\Modules\Payments\Concerns;

use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Models\Product;
use App\Modules\Payments\Models\States\PaymentState;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasPayments
{
    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    public function getTotalPaidAmount(): float
    {
        return $this->payments()->whereState('status', [PaymentState\Paid::class])->sum('amount');
    }

    public function getAmountOwed(): float
    {
        return $this->getPayableAmount() - $this->getTotalPaidAmount();
    }

    public function getPayableAmount(): float
    {
        if (!isset($this->amount)) {
            throw new \RuntimeException('Model must either have an amount property or override getPayableAmount()');
        }
        return $this->amount;
    }

    public function getPayableDescription(): string
    {
        return class_basename($this) . " #{$this->id}";
    }

    public function getUser(): User
    {
        if (!method_exists($this, 'user')) {
            throw new \RuntimeException('Model must have a user() relationship or override getUser()');
        }

        return $this->user;
    }

    public function getProduct(): Product
    {
        if (!method_exists($this, 'product')) {
            throw new \RuntimeException('Model must have a product() relationship or override getProduct()');
        }

        return $this->product;
    }

    public function getLineItems(): array
    {
        return [
            [
                'price' => $this->getProduct()->stripe_price_id,
                'quantity' => 1,
            ]
        ];
    }

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
