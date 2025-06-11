<?php

namespace CorvMC\PracticeSpace\Models\States\BookingState;

use CorvMC\PracticeSpace\Models\States\BookingState;
use Filament\Forms;
use Filament\Support\Exceptions\Cancel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Confirmed State
 *
 * This state represents a booking that has been confirmed by the member.
 * Staff can check in the member on the day of the booking.
 * If the member doesn't show up, staff can mark the booking as a no-show.
 */
class ConfirmedState extends BookingState
{
    public static string $name = 'confirmed';
    public static string $label = 'Confirmed';
    public static ?string $verb = 'Confirm';
    public static string $icon = 'heroicon-o-check-circle';
    public static string $color = 'success';

    public static function getAllowedTransitions(Model $model): array
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $startTime = $model->start_time;
        
        $transitions = [];
        
        if ($user->isAdmin() && $startTime->subMinutes(15)->isPast()) {
            $transitions[] = CheckedInState::class;
        }
        
        if ($user->isAdmin() && $startTime->addMinutes(15)->isPast()) {
            $transitions[] = NoShowState::class;
        }
        
        if ($startTime->isFuture()) {
            $transitions[] = CancelledState::class;
        }
        
        return $transitions;
    }
}
