<?php

namespace SocialDept\AtpClient\Exceptions;

use SocialDept\AtpClient\Enums\RefreshFailureReason;

class OAuthSessionInvalidException extends AuthenticationException
{
    public static function missingRefreshToken(): self
    {
        return (new self('OAuth session is invalid: refresh token is missing'))
            ->withReason(RefreshFailureReason::MissingRefreshToken);
    }

    public static function expiredRefreshToken(): self
    {
        return (new self('OAuth session is invalid: refresh token has expired'))
            ->withReason(RefreshFailureReason::InvalidGrant);
    }

    public static function refreshFailed(string $reason): self
    {
        return new self("OAuth token refresh failed: {$reason}");
    }
}
