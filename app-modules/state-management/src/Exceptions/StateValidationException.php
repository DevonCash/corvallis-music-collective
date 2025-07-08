<?php

namespace CorvMC\StateManagement\Exceptions;

use Illuminate\Database\Eloquent\Model;

/**
 * Exception thrown when state validation fails.
 */
class StateValidationException extends StateException
{
    protected array $validationErrors = [];

    public function __construct(
        string $state,
        Model $model,
        array $validationErrors = [],
        array $context = []
    ) {
        $this->validationErrors = $validationErrors;
        
        $message = sprintf(
            'State validation failed for "%s" on %s #%s',
            $state,
            class_basename($model),
            $model->getKey()
        );

        if (!empty($validationErrors)) {
            $message .= ': ' . implode(', ', $validationErrors);
        }

        $context = array_merge($context, [
            'state' => $state,
            'model_type' => get_class($model),
            'model_id' => $model->getKey(),
            'validation_errors' => $validationErrors,
        ]);

        parent::__construct($message, 0, null, $context);
    }

    public function getUserMessage(): string
    {
        if (empty($this->validationErrors)) {
            return 'The current information is not valid for this status change.';
        }

        $errors = array_map(function ($error) {
            return "• {$error}";
        }, $this->validationErrors);

        return "Please fix the following issues:\n" . implode("\n", $errors);
    }

    public function getErrorCode(): string
    {
        return 'STATE_VALIDATION_FAILED';
    }

    /**
     * Get the validation errors that caused this exception.
     */
    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }
}