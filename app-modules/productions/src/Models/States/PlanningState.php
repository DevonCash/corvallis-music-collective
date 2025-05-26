<?php

namespace CorvMC\Productions\Models\States;

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

    public static function getAllowedTransitions(): array
    {
        return [
            'published' => 'Publish to Schedule',
            'cancelled' => 'Cancel Production',
        ];
    }

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

    public function canTransitionTo(string $state): bool
    {
        if (!in_array($state, static::$allowedTransitions)) {
            return false;
        }

        // If transitioning to published, validate required fields
        if ($state === 'published') {
            $errors = $this->getPublishingValidationErrors();
            if (!empty($errors)) {
                throw new \Exception('Cannot publish production: ' . implode(', ', $errors));
            }
            return true;
        }

        return true;
    }

    protected function validateForPublishing(): bool
    {
        return empty($this->getPublishingValidationErrors());
    }

    protected function getPublishingValidationErrors(): array
    {
        $production = $this->model;
        $errors = [];

        if (empty($production->title)) {
            $errors[] = 'Title is required';
        }

        if (empty($production->venue_id)) {
            $errors[] = 'Venue is required';
        }

        if (empty($production->start_date)) {
            $errors[] = 'Start Time is required';
        }

        if (empty($production->poster)) {
            $errors[] = 'Poster is required';
        }

        return $errors;
    }
} 