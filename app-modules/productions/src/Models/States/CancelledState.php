<?php

namespace CorvMC\Productions\Models\States;

use Filament\Forms;
use Illuminate\Database\Eloquent\Model;

/**
 * Cancelled State
 *
 * This state represents a production that has been cancelled.
 * This is a terminal state - no further transitions are allowed.
 */
class CancelledState extends ProductionState
{
    protected static string $name = 'cancelled';
    protected static string $label = 'Cancelled';
    protected static string $icon = 'heroicon-o-x-circle';
    protected static string $color = 'danger';
    protected static array $allowedTransitions = [
        'archived',
    ];

    public static function getForm(): array
    {
        return [
            Forms\Components\Textarea::make('cancellation_reason')
                ->label('Cancellation Reason')
                ->placeholder('Explain why the production was cancelled')
                ->required(),
            Forms\Components\DateTimePicker::make('cancelled_at')
                ->label('Cancellation Date/Time')
                ->default(now())
                ->required(),
            Forms\Components\Toggle::make('refund_issued')
                ->label('Refund Issued')
                ->helperText('Confirm if any refunds have been processed')
                ->required(),
        ];
    }

    public static function canTransitionTo(Model $model, string $state): bool
    {
        return in_array($state, static::$allowedTransitions);
    }
} 