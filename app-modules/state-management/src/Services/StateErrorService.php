<?php

namespace CorvMC\StateManagement\Services;

use CorvMC\StateManagement\Exceptions\StateException;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;

/**
 * Service for handling and displaying user-friendly error messages.
 */
class StateErrorService
{
    /**
     * Display a user-friendly error notification in Filament.
     */
    public static function displayError(StateException $exception): void
    {
        $notification = Notification::make()
            ->title('Status Change Failed')
            ->body(static::formatErrorMessage($exception))
            ->danger()
            ->persistent();

        // Add action buttons for common errors
        if (method_exists($exception, 'getValidationErrors')) {
            $notification->actions([
                \Filament\Notifications\Actions\Action::make('details')
                    ->label('View Details')
                    ->button()
                    ->close(),
            ]);
        }

        $notification->send();
    }

    /**
     * Format error message for user display.
     */
    public static function formatErrorMessage(StateException $exception): string
    {
        $userMessage = $exception->getUserMessage();
        
        // Add helpful context for common error types
        return match ($exception->getErrorCode()) {
            'STATE_VALIDATION_FAILED' => static::addValidationHelp($userMessage),
            'STATE_TRANSITION_INVALID' => static::addTransitionHelp($userMessage),
            'STATE_NOT_FOUND' => static::addNotFoundHelp($userMessage),
            'STATE_CONFIGURATION_ERROR' => static::addConfigurationHelp($userMessage),
            default => $userMessage,
        };
    }

    /**
     * Add helpful context for validation errors.
     */
    private static function addValidationHelp(string $message): string
    {
        return $message . "\n\nPlease review the required fields and try again.";
    }

    /**
     * Add helpful context for transition errors.
     */
    private static function addTransitionHelp(string $message): string
    {
        return $message . "\n\nYou may need to complete certain steps before this status change is allowed.";
    }

    /**
     * Add helpful context for not found errors.
     */
    private static function addNotFoundHelp(string $message): string
    {
        return $message . "\n\nThis may indicate a system issue. Please try refreshing the page.";
    }

    /**
     * Add helpful context for configuration errors.
     */
    private static function addConfigurationHelp(string $message): string
    {
        return $message . "\n\nThis appears to be a system configuration issue.";
    }

    /**
     * Create a success notification for state transitions.
     */
    public static function displaySuccess(string $fromState, string $toState, string $itemType = 'item'): void
    {
        $message = static::formatSuccessMessage($fromState, $toState, $itemType);
        
        Notification::make()
            ->title('Status Updated Successfully')
            ->body($message)
            ->success()
            ->send();
    }

    /**
     * Format success message for user display.
     */
    public static function formatSuccessMessage(string $fromState, string $toState, string $itemType = 'item'): string
    {
        return sprintf(
            'The %s status has been changed from "%s" to "%s".',
            $itemType,
            ucfirst($fromState),
            ucfirst($toState)
        );
    }

    /**
     * Create a warning notification for potentially risky transitions.
     */
    public static function displayWarning(string $message, array $actions = []): void
    {
        $notification = Notification::make()
            ->title('Please Confirm')
            ->body($message)
            ->warning()
            ->persistent();

        if (!empty($actions)) {
            $notification->actions($actions);
        }

        $notification->send();
    }

    /**
     * Get user-friendly state names for display.
     */
    public static function getDisplayStateName(string $stateName): string
    {
        return match ($stateName) {
            'planning' => 'Planning',
            'published' => 'Published',
            'active' => 'Active',
            'finished' => 'Finished',
            'archived' => 'Archived',
            'cancelled' => 'Cancelled',
            'rescheduled' => 'Rescheduled',
            'pre_production' => 'Pre-Production',
            default => ucfirst($stateName),
        };
    }

    /**
     * Get contextual help text for states.
     */
    public static function getStateHelpText(string $stateName): string
    {
        return match ($stateName) {
            'planning' => 'This item is currently being planned and organized.',
            'published' => 'This item is publicly visible and available.',
            'active' => 'This item is currently in progress.',
            'finished' => 'This item has been completed and needs wrap-up.',
            'archived' => 'This item has been archived and is no longer active.',
            'cancelled' => 'This item has been cancelled.',
            'rescheduled' => 'This item has been rescheduled to a new date.',
            default => 'Current status: ' . ucfirst($stateName),
        };
    }

    /**
     * Generate contextual error suggestions based on the error and current state.
     */
    public static function generateErrorSuggestions(StateException $exception): array
    {
        $suggestions = [];
        $context = $exception->getContext();

        if ($exception->getErrorCode() === 'STATE_VALIDATION_FAILED') {
            $suggestions[] = 'Review the required fields and ensure all information is complete.';
            $suggestions[] = 'Check that all dates are valid and in the correct format.';
        }

        if ($exception->getErrorCode() === 'STATE_TRANSITION_INVALID') {
            $fromState = $context['from_state'] ?? '';
            $suggestions[] = "From '{$fromState}' state, you may need to complete certain steps first.";
            $suggestions[] = 'Contact an administrator if you believe this transition should be allowed.';
        }

        return $suggestions;
    }
}