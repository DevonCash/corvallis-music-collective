<?php

namespace CorvMC\PracticeSpace\Models\States\BookingState;

use CorvMC\PracticeSpace\Models\States\BookingState;
use Filament\Forms;
use Illuminate\Database\Eloquent\Model;

/**
 * Cancelled State
 * 
 * This state represents a booking that has been cancelled.
 */
class CancelledState extends BookingState
{
    /**
     * The name of the state.
     */
    public static string $name = 'cancelled';
    public static ?string $verb = 'Cancel';
    public static string $label = 'Cancelled';
    public static string $icon = 'heroicon-o-x-circle';
    public static string $color = 'danger';

    /**
     * Get the form schema for transitioning to this state.
     */
    public static function getForm(): array
    {
        return [];
    }
} 