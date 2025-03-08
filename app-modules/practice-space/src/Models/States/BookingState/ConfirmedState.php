<?php

namespace CorvMC\PracticeSpace\Models\States\BookingState;

use CorvMC\PracticeSpace\Models\States\BookingState;
use Filament\Forms;
use Illuminate\Database\Eloquent\Model;

/**
 * Confirmed State
 * 
 * This state represents a booking that has been confirmed.
 */
class ConfirmedState extends BookingState
{
    public static string $name = 'confirmed';
    public static string $label = 'Confirmed';
    public static ?string $verb = 'Confirm';
    public static string $icon = 'heroicon-o-check-circle';
    public static string $color = 'success';
    public static array $allowedTransitions = [CheckedInState::class, CancelledState::class, NoShowState::class];
} 