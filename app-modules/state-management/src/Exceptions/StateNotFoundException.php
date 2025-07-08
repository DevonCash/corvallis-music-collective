<?php

namespace CorvMC\StateManagement\Exceptions;

use Illuminate\Database\Eloquent\Model;

/**
 * Exception thrown when a state is not found or doesn't exist.
 */
class StateNotFoundException extends StateException
{
    public function __construct(
        string $stateName,
        ?Model $model = null,
        array $availableStates = [],
        array $context = []
    ) {
        $message = sprintf('State "%s" not found', $stateName);
        
        if ($model) {
            $message .= sprintf(' for %s #%s', class_basename($model), $model->getKey());
        }

        if (!empty($availableStates)) {
            $message .= '. Available states: ' . implode(', ', $availableStates);
        }

        $context = array_merge($context, [
            'state_name' => $stateName,
            'model_type' => $model ? get_class($model) : null,
            'model_id' => $model ? $model->getKey() : null,
            'available_states' => $availableStates,
        ]);

        parent::__construct($message, 0, null, $context);
    }

    public function getUserMessage(): string
    {
        $context = $this->getContext();
        
        return sprintf(
            'The status "%s" is not recognized. Please contact support if this problem persists.',
            ucfirst($context['state_name'])
        );
    }

    public function getErrorCode(): string
    {
        return 'STATE_NOT_FOUND';
    }
}