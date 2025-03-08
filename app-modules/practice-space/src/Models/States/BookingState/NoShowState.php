<?php

namespace CorvMC\PracticeSpace\Models\States\BookingState;

use CorvMC\PracticeSpace\Models\States\BookingState;
use Filament\Forms;
use Illuminate\Database\Eloquent\Model;

/**
 * NoShow State
 * 
 * This state represents a booking where the user did not show up.
 */
class NoShowState extends BookingState
{
    public static string $label = 'No Show';
    public static string $icon = 'heroicon-o-user-minus';
    public static string $color = 'danger';
    public static array $allowedTransitions = [];

    /**
     * Get the form schema for transitioning to this state.
     */
    public static function getForm(): array
    {
        return [
            Forms\Components\Textarea::make('notes')
                ->label('No-Show Notes')
                ->placeholder('Add any notes about this no-show')
                ->required(),
                
            Forms\Components\Toggle::make('charge_cancellation_fee')
                ->label('Charge cancellation fee')
                ->default(true),
        ];
    }
} 