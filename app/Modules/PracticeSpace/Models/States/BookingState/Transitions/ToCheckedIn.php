<?php

namespace App\Modules\PracticeSpace\Models\States\BookingState\Transitions;

use App\Modules\Payments\Models\States\PaymentState\PaymentState;
use App\Modules\PracticeSpace\Models\Booking;
use App\Modules\PracticeSpace\Models\States\BookingState\CheckedIn;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Illuminate\Support\HtmlString;

class ToCheckedIn extends BookingTransition
{
    public static string $label = 'Check In';
    public static string $color = 'success';
    public static string $to_state = CheckedIn::class;

    public function __construct(
        protected readonly Booking $booking,
        private readonly bool $cash_payment = false
    ) {}
    public function canTransition(): bool
    {
        return $this->booking->start_time->subMinutes(10)->isPast();
    }

    public function handle(): Booking
    {
        $this->booking->state = new CheckedIn($this->booking);

        if (!$this->booking->getAmountOwed() > 0 && $this->cash_payment) {
            $this->booking->payments->create([
                'user_id' => $this->booking->user_id,
                'product_id' => $this->booking->product_id,
                'amount' => $this->booking->getAmountOwed(),
                'method' => 'cash',
                'state' => new PaymentState\Completed()
            ]);
        }

        $this->booking->save();
        return $this->booking;
    }

    public function form(): array
    {
        if($this->booking->getAmountOwed() <= 0) {
            return [];
        }

        return [
            Placeholder::make('Amount owed')->content(new HtmlString(
                <<<HTML
                <table>
                    <tbody>
                        <tr>
                            <td>Hourly rate</td>
                            <td>Hours booked</td>
                            <td>Total</td>
                        </tr>
                        <tr>
                            <td>{$this->booking->getPrice()}</td>
                            <td>{$this->booking->duration}</td>
                            <td class='text-bold'>{$this->booking->getPayableAmount()}</td>
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
