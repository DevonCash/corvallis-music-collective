<?php

namespace App\Modules\PracticeSpace\Models\States\BookingState\Transitions;

use App\Modules\PracticeSpace\Models\States\BookingState\Confirmed;

class ToConfirmed extends BookingTransition
{
    public static string $label = 'Confirm';
    public static string $color = 'success';
    public static string $to_state = Confirmed::class;
    public function canTransition(): bool
    {
        return $this->booking->start_time->subDays(3)->startOfDay()->isPast();
    }
}
