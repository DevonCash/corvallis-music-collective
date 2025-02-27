<?php

namespace App\Modules\PracticeSpace\Models\States\BookingState\Transitions;

use App\Modules\PracticeSpace\Models\States\BookingState\Cancelled;

class ToCancelled extends BookingTransition
{
    public static string $label = 'Cancel';
    public static string $color = 'danger';
    public static string $to_state = Cancelled::class;
}
