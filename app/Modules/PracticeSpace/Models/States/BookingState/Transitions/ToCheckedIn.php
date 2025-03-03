<?php

namespace App\Modules\PracticeSpace\Models\States\BookingState\Transitions;

use App\Modules\Payments\Facades\Payment;
use App\Modules\Payments\Models\States\PaymentState;
use App\Modules\PracticeSpace\Models\Booking;
use App\Modules\PracticeSpace\Models\States\BookingState\CheckedIn;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Toggle;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class ToCheckedIn extends BookingTransition
{
    public static string $label = 'Check In';
    public static string $color = 'success';
    public static string $to_state = CheckedIn::class;

    public function __construct(
        protected readonly Booking $booking,
        private readonly ?array $formData = null
    ) {}
    
    /**
     * Check if the booking can transition to CheckedIn state
     * Conditions:
     * 1. We're within 10 minutes of the booking start time
     * 2. The booking is already paid for or cash payment is being made
     */
    public function canTransition(): bool
    {
        // Check if we're within the time window for check-in
        $withinTimeWindow = $this->booking->start_time->subMinutes(10)->isPast();
        
        // Check if booking is paid or cash payment is being made
        $isPaymentValid = $this->booking->isPaid() || ($this->formData['cashPayment'] ?? false);
        
        return $withinTimeWindow && $isPaymentValid;
    }

    /**
     * Process the transition to CheckedIn state
     */
    public function handle(): Booking
    {
        // If a cash payment is being made at check-in time and the booking isn't fully paid yet
        if (($this->formData['cashPayment'] ?? false) && !$this->booking->isPaid()) {
            // Use the Payment facade to create a cash payment
            $payment = Payment::createCashPayment($this->booking);
            
            if (!$payment) {
                Log::error("Failed to process cash payment for booking {$this->booking->id}");
            }
        }
        
        // Update the booking state to CheckedIn
        $this->booking->state = new CheckedIn($this->booking);
        $this->booking->save();
        
        return $this->booking;
    }

    /**
     * Define the form fields for this transition
     * Only show payment options if payment is required
     */
    public function form(): array
    {
        // If no payment is needed, return an empty form
        if ($this->booking->isPaid()) {
            return [];
        }

        // Show payment information and options
        return [
            Placeholder::make('Amount owed')->content(new HtmlString(
                <<<HTML
                <table class='w-full  '>
                    <tbody>
                        <tr class='bg-gray-100'>
                            <td class='p-3 border-black border'>Hourly rate</td>
                            <td class='p-3 border-black border'>Hours booked</td>
                            <td class='p-3 border-black border'>Total</td>
                        </tr>
                        <tr>
                            <td class='p-3 border-black border'>\${$this->booking->getPrice()}</td>
                            <td class='p-3 border-black border'>{$this->booking->duration}</td>
                            <td class='p-3 border-black border font-bold'>\${$this->booking->calculateAmount()}</td>
                        </tr>
                    </tbody>
                </table>
                HTML
            )),
            Toggle::make('cash_payment')
                ->label('Paid in cash')
                ->default(false)
        ];
    }
}
