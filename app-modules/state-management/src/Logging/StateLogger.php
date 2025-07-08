<?php

namespace CorvMC\StateManagement\Logging;

use CorvMC\StateManagement\Exceptions\StateException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

/**
 * Centralized logging service for state management operations.
 */
class StateLogger
{
    /**
     * Log a successful state transition.
     */
    public static function logTransition(
        Model $model,
        string $fromState,
        string $toState,
        array $data = [],
        ?string $userId = null
    ): void {
        $context = [
            'model_type' => get_class($model),
            'model_id' => $model->getKey(),
            'from_state' => $fromState,
            'to_state' => $toState,
            'transition_data' => $data,
            'user_id' => $userId ?: Auth::id(),
            'timestamp' => now()->toISOString(),
        ];

        Log::info('State transition successful', $context);
    }

    /**
     * Log a failed state transition.
     */
    public static function logTransitionError(
        Model $model,
        string $fromState,
        string $toState,
        StateException $exception,
        array $additionalContext = []
    ): void {
        $context = array_merge([
            'model_type' => get_class($model),
            'model_id' => $model->getKey(),
            'from_state' => $fromState,
            'to_state' => $toState,
            'error_code' => $exception->getErrorCode(),
            'error_message' => $exception->getMessage(),
            'user_message' => $exception->getUserMessage(),
            'exception_context' => $exception->getContext(),
            'user_id' => Auth::id(),
            'timestamp' => now()->toISOString(),
        ], $additionalContext);

        Log::error('State transition failed', $context);
    }

    /**
     * Log state validation errors.
     */
    public static function logValidationError(
        Model $model,
        string $state,
        array $validationErrors,
        array $additionalContext = []
    ): void {
        $context = array_merge([
            'model_type' => get_class($model),
            'model_id' => $model->getKey(),
            'state' => $state,
            'validation_errors' => $validationErrors,
            'user_id' => Auth::id(),
            'timestamp' => now()->toISOString(),
        ], $additionalContext);

        Log::warning('State validation failed', $context);
    }

    /**
     * Log state configuration issues.
     */
    public static function logConfigurationError(
        string $issue,
        string $stateClass = '',
        array $additionalContext = []
    ): void {
        $context = array_merge([
            'issue' => $issue,
            'state_class' => $stateClass,
            'timestamp' => now()->toISOString(),
        ], $additionalContext);

        Log::critical('State configuration error', $context);
    }

    /**
     * Log when an invalid state is encountered.
     */
    public static function logInvalidState(
        string $stateName,
        ?Model $model = null,
        array $availableStates = [],
        array $additionalContext = []
    ): void {
        $context = array_merge([
            'state_name' => $stateName,
            'model_type' => $model ? get_class($model) : null,
            'model_id' => $model ? $model->getKey() : null,
            'available_states' => $availableStates,
            'timestamp' => now()->toISOString(),
        ], $additionalContext);

        Log::error('Invalid state encountered', $context);
    }

    /**
     * Log state-related performance metrics.
     */
    public static function logPerformanceMetric(
        string $operation,
        float $duration,
        array $additionalContext = []
    ): void {
        $context = array_merge([
            'operation' => $operation,
            'duration_ms' => round($duration * 1000, 2),
            'timestamp' => now()->toISOString(),
        ], $additionalContext);

        Log::info('State operation performance', $context);
    }

    /**
     * Log bulk state operations.
     */
    public static function logBulkOperation(
        string $operation,
        int $recordsProcessed,
        int $successCount,
        int $failureCount,
        array $errors = []
    ): void {
        $context = [
            'operation' => $operation,
            'records_processed' => $recordsProcessed,
            'success_count' => $successCount,
            'failure_count' => $failureCount,
            'errors' => $errors,
            'success_rate' => $recordsProcessed > 0 ? round(($successCount / $recordsProcessed) * 100, 2) : 0,
            'timestamp' => now()->toISOString(),
        ];

        if ($failureCount > 0) {
            Log::warning('Bulk state operation completed with errors', $context);
        } else {
            Log::info('Bulk state operation completed successfully', $context);
        }
    }
}