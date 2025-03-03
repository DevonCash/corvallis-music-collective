<?php

namespace App\Modules\Payments\Facades;

use App\Modules\Payments\Contracts\Payable;
use App\Modules\Payments\Models\Payment as PaymentModel;
use App\Modules\Payments\Services\PaymentService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Facade;

/**
 * @method static array refundPayable(\App\Modules\Payments\Contracts\Payable $payable, bool $processExternalRefund = true)
 * @method static mixed createStripeCheckoutPayment()
 * @method static \App\Modules\Payments\Models\Payment|null createCashPayment(\App\Modules\Payments\Contracts\Payable $payable, array $additionalAttributes = [])
 * 
 * @see \App\Modules\Payments\Services\PaymentService
 */
class Payment extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'payment';
    }
} 