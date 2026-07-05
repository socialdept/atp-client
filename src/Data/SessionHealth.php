<?php

namespace SocialDept\AtpClient\Data;

use SocialDept\AtpClient\Enums\RefreshFailureReason;

/**
 * The result of a cheap authenticated liveness probe ({@see \SocialDept\AtpClient\AtpClient::probe()}).
 *
 * Distinguishes the three outcomes a keep-alive job needs:
 *  - healthy: the session works (and was refreshed under the hood if needed)
 *  - terminal: the grant is dead or the account is gone/inactive → flag for reconnect
 *  - transient (`!reachable`): network/5xx/rate-limited → leave alone, retry later
 *
 * `needsRefresh` is a fourth, softer state: the access token is stale but the
 * refresh token may still be good (the caller can force a refresh).
 */
class SessionHealth
{
    public function __construct(
        public readonly bool $reachable,
        public readonly bool $accessValid,
        public readonly bool $accountActive,
        public readonly ?string $status = null,
        public readonly ?RefreshFailureReason $reason = null,
    ) {}

    public static function healthy(): self
    {
        return new self(reachable: true, accessValid: true, accountActive: true);
    }

    /**
     * Access token is stale but the account is live and the refresh token may
     * still work — the caller should force a refresh.
     */
    public static function staleAccess(): self
    {
        return new self(reachable: true, accessValid: false, accountActive: true);
    }

    /**
     * The account exists but is takendown/suspended/deactivated (terminal).
     */
    public static function inactive(?string $status): self
    {
        return new self(
            reachable: true,
            accessValid: true,
            accountActive: false,
            status: $status,
            reason: RefreshFailureReason::AccountInactive,
        );
    }

    /**
     * The grant is dead (invalid_grant / account not found) — reconnect required.
     */
    public static function terminal(RefreshFailureReason $reason): self
    {
        return new self(reachable: true, accessValid: false, accountActive: false, reason: $reason);
    }

    /**
     * Could not get an authoritative answer (network / 5xx / rate limit) — retry later.
     */
    public static function unreachable(?RefreshFailureReason $reason = null): self
    {
        return new self(reachable: false, accessValid: false, accountActive: false, reason: $reason);
    }

    public function isHealthy(): bool
    {
        return $this->reachable && $this->accessValid && $this->accountActive;
    }

    /**
     * Live account whose access token is stale — a forced refresh is worthwhile.
     */
    public function needsRefresh(): bool
    {
        return $this->reachable && ! $this->accessValid && $this->accountActive && $this->reason === null;
    }

    /**
     * The session is dead — flag the user for reconnect. Never true for a
     * transient (unreachable) result.
     */
    public function isTerminal(): bool
    {
        if (! $this->reachable) {
            return false;
        }

        return ! $this->accountActive || ($this->reason?->isTerminal() ?? false);
    }
}
