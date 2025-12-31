<?php

namespace SocialDept\AtpClient\Exceptions;

class OAuthSessionInvalidException extends AuthenticationException
{
    public static function missingRefreshToken(): self
    {
        return new self('OAuth session is invalid: refresh token is missing');
    }

    public static function expiredRefreshToken(): self
    {
        return new self('OAuth session is invalid: refresh token has expired');
    }

    public static function refreshFailed(string $reason): self
    {
        return new self("OAuth token refresh failed: {$reason}");
    }
}
