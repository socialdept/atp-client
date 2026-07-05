<?php

namespace SocialDept\AtpClient\Enums;

/**
 * Structured reason a token refresh (or session probe) failed.
 *
 * Terminal reasons mean the grant is dead and the user must re-authenticate.
 * Transient reasons are retryable and must never flag a session as invalid.
 */
enum RefreshFailureReason: string
{
    // --- Terminal (reconnect required) ---
    case InvalidGrant = 'invalid_grant';           // revoked / consumed / expired refresh token
    case InvalidClient = 'invalid_client';         // client assertion rejected
    case MissingRefreshToken = 'missing_refresh_token';
    case AccountInactive = 'account_inactive';     // getSession active:false — takendown/suspended/deactivated
    case AccountNotFound = 'account_not_found';    // "could not find user info" — account deleted

    // --- Transient (retry, never flag) ---
    case UseDpopNonce = 'use_dpop_nonce';
    case TemporarilyUnavailable = 'temporarily_unavailable';
    case SlowDown = 'slow_down';
    case RateLimited = 'rate_limited';             // HTTP 429
    case ServerError = 'server_error';             // 5xx / HTML proxy error
    case Network = 'network';                      // connection failure / status 0
    case Unknown = 'unknown';                      // unmapped — transient by policy

    /**
     * The grant is dead — the user must reconnect.
     */
    public function isTerminal(): bool
    {
        return match ($this) {
            self::InvalidGrant,
            self::InvalidClient,
            self::MissingRefreshToken,
            self::AccountInactive,
            self::AccountNotFound => true,
            default => false,
        };
    }

    /**
     * Retryable — never flag the session. Unknown is transient by policy so a
     * body we could not parse never locks out a live user.
     */
    public function isTransient(): bool
    {
        return ! $this->isTerminal();
    }

    /**
     * Map to the legacy reason strings the SessionRefreshFailed event and
     * downstream listeners (e.g. Offprint's PERMANENT_REASONS) already expect,
     * so adding the typed reason is backwards compatible.
     */
    public function legacyReason(): string
    {
        return match ($this) {
            self::MissingRefreshToken => 'missing',
            self::InvalidGrant, self::AccountInactive, self::AccountNotFound => 'invalid',
            self::InvalidClient => 'auth_failed',
            default => 'transient',
        };
    }
}
