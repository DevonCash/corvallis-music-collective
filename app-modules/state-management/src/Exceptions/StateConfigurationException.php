<?php

namespace CorvMC\StateManagement\Exceptions;

/**
 * Exception thrown when there's an issue with state configuration.
 */
class StateConfigurationException extends StateException
{
    public function __construct(
        string $issue,
        string $stateClass = '',
        array $context = []
    ) {
        $message = 'State configuration error';
        
        if ($stateClass) {
            $message .= " in {$stateClass}";
        }
        
        $message .= ": {$issue}";

        $context = array_merge($context, [
            'issue' => $issue,
            'state_class' => $stateClass,
        ]);

        parent::__construct($message, 0, null, $context);
    }

    public function getUserMessage(): string
    {
        return 'There is a configuration issue with the state management system. Please contact support.';
    }

    public function getErrorCode(): string
    {
        return 'STATE_CONFIGURATION_ERROR';
    }
}