<?php

namespace App\Modules\PracticeSpace;

use Spatie\LaravelSettings\Settings;

class BookingSettings extends Settings
{

    // TODO: Booking policy settings
    public static function group(): string
    {
        return 'booking';
    }
}
