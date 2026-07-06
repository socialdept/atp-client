<?php

namespace SocialDept\AtpClient\Exceptions;

class TransientAuthFailureException extends AuthenticationException
{
    public static function fromResponse(string $body, int $statusCode): self
    {
        return new self("Token refresh failed (transient, HTTP {$statusCode}): {$body}");
    }

    /**
     * The per-DID refresh lock was contended and no rotated token was available
     * to adopt. Retrying (not replaying a consumed token) is the safe response.
     */
    public static function lockContended(): self
    {
        return new self('Token refresh lock contended; retry');
    }
}
