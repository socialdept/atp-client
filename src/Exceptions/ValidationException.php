<?php

namespace SocialDept\AtpClient\Exceptions;

class ValidationException extends \Exception
{
    public function __construct(
        public readonly array $errors,
        string $message = 'Response validation failed',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get validation errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
