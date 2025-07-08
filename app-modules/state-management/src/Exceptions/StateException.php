<?php

namespace CorvMC\StateManagement\Exceptions;

use Exception;

/**
 * Base exception for all state management related errors.
 */
abstract class StateException extends Exception
{
    protected array $context = [];

    public function __construct(string $message = "", int $code = 0, ?Exception $previous = null, array $context = [])
    {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    /**
     * Get additional context information about the error.
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Get a user-friendly error message.
     */
    abstract public function getUserMessage(): string;

    /**
     * Get the error code that can be used for logging and tracking.
     */
    abstract public function getErrorCode(): string;
}