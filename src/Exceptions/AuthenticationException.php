<?php

namespace SocialDept\AtpClient\Exceptions;

use SocialDept\AtpClient\Enums\RefreshFailureReason;

class AuthenticationException extends \Exception
{
    /**
     * Structured reason this auth failure occurred, when known. Lets callers
     * decide terminal-vs-transient without re-parsing the message. Optional for
     * backwards compatibility.
     */
    public ?RefreshFailureReason $reason = null;

    /**
     * Attach a structured failure reason and return the exception for fluent throws.
     */
    public function withReason(?RefreshFailureReason $reason): static
    {
        $this->reason = $reason;

        return $this;
    }
}
