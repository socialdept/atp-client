<?php

namespace SocialDept\AtpClient\Data;

use SocialDept\AtpClient\Enums\RevocationFailure;
use SocialDept\AtpClient\Exceptions\AuthenticationException;

/**
 * The outcome of revoking a stored session ({@see \SocialDept\AtpClient\Auth\OAuthEngine::revoke()}).
 *
 * The session is always forgotten locally. `revoked` says whether the server
 * also confirmed it, and `failure` says why not.
 */
class RevocationResult
{
    public function __construct(
        public readonly string $did,
        public readonly bool $revoked,
        public readonly ?RevocationFailure $failure = null,
        public readonly ?int $status = null,
        public readonly ?string $error = null,
    ) {
    }

    public static function revoked(string $did): self
    {
        return new self($did, revoked: true);
    }

    public static function failed(string $did, RevocationFailure $failure, ?int $status = null, ?string $error = null): self
    {
        return new self($did, revoked: false, failure: $failure, status: $status, error: $error);
    }

    /**
     * Whether a session was stored for the DID before the call.
     */
    public function hadSession(): bool
    {
        return $this->failure !== RevocationFailure::NoSession;
    }

    /**
     * Throw when the server did not confirm the revocation, for callers that
     * must know the grant is dead.
     *
     * @throws AuthenticationException
     */
    public function throw(): static
    {
        if ($this->revoked) {
            return $this;
        }

        $message = "Could not revoke the session for {$this->did}: {$this->failure->value}";

        if ($this->status !== null) {
            $message .= " (HTTP {$this->status})";
        }

        throw new AuthenticationException($this->error !== null ? "{$message}: {$this->error}" : $message);
    }
}
