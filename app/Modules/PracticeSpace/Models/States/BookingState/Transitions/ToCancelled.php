<?php

namespace App\Modules\PracticeSpace\Models\States\BookingState\Transitions;

use App\Modules\Payments\Facades\Payment;
use App\Modules\PracticeSpace\Models\Booking;
use App\Modules\PracticeSpace\Models\States\BookingState\Cancelled;
use Illuminate\Support\Facades\Log;

class ToCancelled extends BookingTransition
{
    public static string $label = 'Cancel';
    public static string $color = 'danger';
    public static string $to_state = Cancelled::class;

    public function __construct(
        protected readonly Booking $booking,
        private readonly ?array $formData = null
    ) {}

    /**
     * Process the transition to Cancelled state
     * If the booking has been pre-paid, issue a refund
     */
    public function handle(): Booking
    {
        // First transition the booking to cancelled state
        $this->booking->state = new Cancelled($this->booking);
        $this->booking->save();

        // Process refunds for any paid payments using the Payment facade
        $refundedPaymentIds = Payment::refundPayable($this->booking);
        
        if (!empty($refundedPaymentIds)) {
            Log::info("Refunded payments for booking {$this->booking->id}: " . implode(', ', $refundedPaymentIds));
        }

        return $this->booking;
    }
}
