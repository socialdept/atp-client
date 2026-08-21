<?php

namespace SocialDept\AtpClient\Data;

/**
 * A verified inter-service auth token.
 *
 * This is what one atproto service presents to another to say "I am this DID,
 * and I am calling this one method on your behalf". The `lxm` claim is what
 * keeps it narrow: a token minted to call one endpoint cannot be replayed
 * against a different one.
 */
class ServiceAuthToken
{
    /**
     * @param  string  $issuer  The calling service or account DID
     * @param  string  $audience  The service identifier it was addressed to
     * @param  string|null  $method  The NSID it authorizes, when bound to one
     * @param  array<string, mixed>  $claims  The full decoded payload
     */
    public function __construct(
        public readonly string $issuer,
        public readonly string $audience,
        public readonly ?string $method,
        public readonly int $expiresAt,
        public readonly array $claims = [],
    ) {
    }

    /**
     * The DID that signed this token — who is calling.
     */
    public function did(): string
    {
        return $this->issuer;
    }

    /**
     * Whether the token authorizes a given method.
     */
    public function authorizes(string $nsid): bool
    {
        return $this->method === null || $this->method === $nsid;
    }

    public function secondsRemaining(?int $now = null): int
    {
        return max(0, $this->expiresAt - ($now ?? time()));
    }

    /**
     * A claim by name.
     */
    public function claim(string $name): mixed
    {
        return $this->claims[$name] ?? null;
    }
}
