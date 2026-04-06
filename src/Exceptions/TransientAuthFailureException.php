<?php

namespace SocialDept\AtpClient\Exceptions;

class TransientAuthFailureException extends AuthenticationException
{
    public static function fromResponse(string $body, int $statusCode): self
    {
        return new self("Token refresh failed (transient, HTTP {$statusCode}): {$body}");
    }
}
