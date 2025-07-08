<?php

namespace CorvMC\StateManagement\Exceptions;

use Illuminate\Database\Eloquent\Model;

/**
 * Exception thrown when an invalid state transition is attempted.
 */
class InvalidStateTransitionException extends StateException
{
    public function __construct(
        string $fromState,
        string $toState,
        Model $model,
        string $reason = '',
        array $context = []
    ) {
        $message = sprintf(
            'Invalid state transition from "%s" to "%s" for %s #%s',
            $fromState,
            $toState,
            class_basename($model),
            $model->getKey()
        );

        if ($reason) {
            $message .= ": {$reason}";
        }

        $context = array_merge($context, [
            'from_state' => $fromState,
            'to_state' => $toState,
            'model_type' => get_class($model),
            'model_id' => $model->getKey(),
            'reason' => $reason,
        ]);

        parent::__construct($message, 0, null, $context);
    }

    public function getUserMessage(): string
    {
        $context = $this->getContext();
        
        return sprintf(
            'Cannot change status from "%s" to "%s". %s',
            ucfirst($context['from_state']),
            ucfirst($context['to_state']),
            $context['reason'] ?: 'This transition is not allowed.'
        );
    }

    public function getErrorCode(): string
    {
        return 'STATE_TRANSITION_INVALID';
    }
}