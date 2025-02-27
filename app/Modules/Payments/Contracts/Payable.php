<?php

namespace App\Modules\Payments\Contracts;

use App\Modules\Payments\Models\Product;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Relations\MorphMany;

interface Payable
{
    public function payments(): MorphMany;
    public function getPayableAmount(): float;
    public function getPayableDescription(): string;
    public function getUser(): User;
    public function getProduct(): Product;
    public function getLineItems(): array;
    public function getCheckoutOptions(): array;
}
