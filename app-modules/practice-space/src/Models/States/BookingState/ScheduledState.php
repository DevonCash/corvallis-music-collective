<?php

namespace CorvMC\PracticeSpace\Models\States\BookingState;

use CorvMC\PracticeSpace\Models\States\BookingState;
use Filament\Forms;

/**
 * Scheduled State
 * 
 * This is the initial state for all new bookings.
 */
class ScheduledState extends BookingState
{
    public static string $name = 'scheduled';
    public static string $label = 'Scheduled';
    public static string $icon = 'heroicon-o-clock';
    public static string $color = 'warning';
    public static array $allowedTransitions = [ConfirmedState::class, CancelledState::class];
} 