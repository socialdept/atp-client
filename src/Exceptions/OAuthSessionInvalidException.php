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

    /**
     * The DPoP key for an existing session is missing (e.g. the key store was
     * wiped or is not shared across hosts). The refresh token is bound to the
     * old key, so refreshing is impossible — the user must reconnect. We throw
     * rather than silently mint a new key, which would guarantee an invalid_grant.
     */
    public static function missingDpopKey(string $did): self
    {
        return (new self("OAuth session is invalid: DPoP key missing for {$did}"))
            ->withReason(RefreshFailureReason::InvalidGrant);
    }
}
