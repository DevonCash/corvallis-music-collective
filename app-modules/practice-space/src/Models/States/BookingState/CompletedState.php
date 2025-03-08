<?php

namespace CorvMC\PracticeSpace\Models\States\BookingState;

use CorvMC\PracticeSpace\Models\States\BookingState;
use Filament\Forms;
use Illuminate\Database\Eloquent\Model;

/**
 * Completed State
 * 
 * This state represents a booking that has been completed.
 */
class CompletedState extends BookingState
{
    public static string $name = 'completed';
    public static string $label = 'Completed';
    public static string $icon = 'heroicon-o-check-badge';
    public static string $color = 'success';
    public static array $allowedTransitions = [];
} 