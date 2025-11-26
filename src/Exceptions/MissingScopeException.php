<?php

namespace SocialDept\AtpClient\Exceptions;

class MissingScopeException extends \Exception
{
    public function __construct(
        public readonly array $required,
        public readonly array $granted,
        ?string $message = null,
    ) {
        parent::__construct($message ?? $this->buildMessage());
    }

    protected function buildMessage(): string
    {
        $required = implode(', ', $this->required);
        $granted = empty($this->granted) ? 'none' : implode(', ', $this->granted);

        return "Missing required scope(s): {$required}. Granted: {$granted}.";
    }
}
