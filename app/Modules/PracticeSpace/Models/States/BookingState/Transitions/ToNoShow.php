<?php

namespace App\Modules\PracticeSpace\Models\States\BookingState\Transitions;

use App\Modules\PracticeSpace\Models\States\BookingState\NoShow;

class ToNoShow extends BookingTransition
{
    public static string $label = 'No Show';
    public static string $color = 'danger';
    public static string $to_state = NoShow::class;
    public function canTransition(): bool
    {
        return $this->booking->start_time->addMinutes(15)->isPast();
    }
}
