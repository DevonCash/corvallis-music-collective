<?php

namespace App\Modules\PracticeSpace\Models\States\BookingState\Transitions;

use App\Modules\PracticeSpace\Models\States\BookingState\Completed;

class ToCompleted extends BookingTransition
{
    public static string $label = 'Check Out';
    public static string $color = 'success';
    public static string $to_state = Completed::class;
}
