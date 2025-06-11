<?php

namespace CorvMC\PracticeSpace\Models\States\BookingState;

use CorvMC\PracticeSpace\Models\States\BookingState;
use Filament\Forms;
use Illuminate\Database\Eloquent\Model;

/**
 * NoShow State
 * 
 * This state represents a booking where the user did not show up.
 * Staff must document their attempt to contact the member before marking as no-show.
 * This is a terminal state - no further transitions are allowed.
 */
class NoShowState extends BookingState
{
    public static string $name = 'no_show';
    public static string $label = 'No Show';
    public static ?string $verb = 'Mark as No Show';
    public static string $icon = 'heroicon-o-x-circle';
    public static string $color = 'danger';
    public static array $allowedTransitions = [];

    /**
     * Get the form schema for transitioning to this state.
     */
    public static function getForm(): array
    {
        return [
            Forms\Components\Textarea::make('no_show_notes')
                ->label('No Show Notes')
                ->placeholder('Add any notes about why the member did not show up')
                ->required(),
        ];
    }
} 