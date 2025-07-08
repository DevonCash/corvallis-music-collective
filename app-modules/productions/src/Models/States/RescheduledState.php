<?php

namespace CorvMC\Productions\Models\States;

use Filament\Forms;
use Illuminate\Database\Eloquent\Model;

/**
 * Rescheduled State
 *
 * This state represents a production that has been rescheduled.
 * Productions can be rescheduled from Planning or Pre-Production states.
 */
class RescheduledState extends ProductionState
{
    protected static string $name = 'rescheduled';
    protected static string $label = 'Rescheduled';
    protected static string $icon = 'heroicon-o-calendar';
    protected static string $color = 'warning';
    protected static array $allowedTransitions = [
        'planning',
        'published',
        'cancelled',
    ];

    public static function getForm(): array
    {
        return [
            Forms\Components\Textarea::make('reschedule_reason')
                ->label('Reschedule Reason')
                ->placeholder('Explain why the production was rescheduled')
                ->required(),
            Forms\Components\DateTimePicker::make('rescheduled_at')
                ->label('Reschedule Date/Time')
                ->default(now())
                ->required(),
            Forms\Components\DateTimePicker::make('new_start_date')
                ->label('New Start Date')
                ->required(),
            Forms\Components\DateTimePicker::make('new_end_date')
                ->label('New End Date')
                ->required(),
            Forms\Components\Toggle::make('notify_attendees')
                ->label('Notify Attendees')
                ->helperText('Send notifications to registered attendees')
                ->default(true),
        ];
    }

    public static function canTransitionTo(Model $model, string $state): bool
    {
        return in_array($state, static::$allowedTransitions);
    }
} 