<?php

namespace CorvMC\Productions\Models\States;

use CorvMC\StateManagement\Exceptions\StateValidationException;
use CorvMC\StateManagement\Logging\StateLogger;
use Filament\Forms;
use Illuminate\Database\Eloquent\Model;

/**
 * Planning State
 *
 * This is the initial state for all new productions.
 * Productions in this state are being planned and organized.
 */
class PlanningState extends ProductionState
{
    protected static string $name = 'planning';
    protected static string $label = 'Planning';
    protected static string $icon = 'heroicon-o-clipboard-document-list';
    protected static string $color = 'gray';
    protected static ?string $verb = 'Plan';
    protected static array $allowedTransitions = [
        'published',
        'cancelled',
    ];

    public static function getForm(): array
    {
        return [
            Forms\Components\Textarea::make('notes')
                ->label('Planning Notes')
                ->placeholder('Add any notes about the planning phase')
                ->required(false),
            Forms\Components\Toggle::make('ready_to_publish')
                ->label('Ready to Publish')
                ->helperText('Confirm that all planning is complete and ready for public viewing')
                ->required(),
        ];
    }

    public static function canTransitionTo(Model $model, string $state): bool
    {
        if (!in_array($state, static::$allowedTransitions)) {
            return false;
        }

        // If transitioning to published, validate required fields
        if ($state === 'published') {
            $errors = static::getPublishingValidationErrors($model);
            if (!empty($errors)) {
                StateLogger::logValidationError($model, $state, $errors);
                throw new StateValidationException($state, $model, $errors);
            }
            return true;
        }

        return true;
    }

    protected static function validateForPublishing(Model $model): bool
    {
        return empty(static::getPublishingValidationErrors($model));
    }

    protected static function getPublishingValidationErrors(Model $model): array
    {
        $errors = [];

        if (empty($model->title)) {
            $errors[] = 'Title is required';
        }

        if (empty($model->venue_id)) {
            $errors[] = 'Venue is required';
        }

        if (empty($model->start_date)) {
            $errors[] = 'Start Time is required';
        }

        if (empty($model->poster)) {
            $errors[] = 'Poster is required';
        }

        return $errors;
    }
} 