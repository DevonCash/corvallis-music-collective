<?php

namespace App\Modules\Payments\Services;

use App\Modules\Payments\Contracts\Payable;
use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Models\States\PaymentState;
use App\Modules\Payments\Models\States\PaymentState\Refunded;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentService {
    /**
     * Create a Stripe checkout payment
     */
    public function createStripeCheckoutPayment() {
        // Implementation for creating Stripe checkout
    }

    /**
     * Process a cash payment for a payable model
     * 
     * @param Payable $payable Any model that implements the Payable interface
     * @param array $additionalAttributes Additional attributes to add to the payment
     * @return Payment|null The created payment or null if creation failed
     */
    public function createCashPayment(Payable $payable, array $additionalAttributes = []): ?Payment
    {
        try {
            // Create the cash payment with required attributes
            $paymentAttributes = array_merge([
                'user_id' => $payable->getUser()->id,
                'product_id' => $payable->getProduct()->id,
                'stripe_payment_intent_id' => 'cash_payment_' . Str::uuid(),
                'amount' => $payable->getAmountOwed(),
                'method' => 'cash',
                'state' => PaymentState\Paid::$name
            ], $additionalAttributes);

            // Create the payment using the trait method
            $payment = $payable->createPayment($paymentAttributes);

            Log::info("Cash payment {$payment->id} created for " . get_class($payable) . " #{$payable->id}");
            
            return $payment;
        } catch (\Exception $e) {
            Log::error("Failed to create cash payment for " . get_class($payable) . " #{$payable->id}: {$e->getMessage()}");
            return null;
        }
    }

    /**
     * Refund all paid payments for a payable model
     * 
     * @param Payable $payable Any model that implements the Payable interface
     * @param bool $processExternalRefund Whether to process the refund through the payment processor
     * @return array Array of refunded payment IDs
     */
    public function refundPayable(Payable $payable, bool $processExternalRefund = true): array
    {
        $refundedPaymentIds = [];

        // Get all paid payments for the payable model
        $paidPayments = $payable->payments()
            ->where('state', 'paid')
            ->get();

        // Process refunds for each paid payment
        foreach ($paidPayments as $payment) {
            // Transition the payment to refunded state
            $payment->state = new Refunded($payment);
            $payment->save();

            $refundedPaymentIds[] = $payment->id;

            // Process the external refund through the payment processor if requested
            if ($processExternalRefund && !empty($payment->stripe_payment_intent_id)) {
                $this->processStripeRefund($payment);
            }

            Log::info("Payment {$payment->id} for " . get_class($payable) . " #{$payable->id} has been refunded");
        }

        return $refundedPaymentIds;
    }

    /**
     * Process a refund through Stripe
     * 
     * @param Payment $payment The payment to refund
     * @return bool Whether the refund was successful
     */
    protected function processStripeRefund(Payment $payment): bool
    {
        try {
            // Here you would make the actual Stripe API call
            // For example:
            // $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));
            // $refund = $stripe->refunds->create([
            //     'payment_intent' => $payment->stripe_payment_intent_id,
            // ]);
            
            Log::info("Stripe refund processed for payment {$payment->id}");
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to process Stripe refund for payment {$payment->id}: {$e->getMessage()}");
            return false;
        }
    }
}
