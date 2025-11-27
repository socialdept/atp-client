<?php

namespace SocialDept\AtpClient\Data;

use Carbon\Carbon;
use SocialDept\AtpClient\Enums\AuthType;

class AccessToken
{
    public function __construct(
        public readonly string $accessJwt,
        public readonly string $refreshJwt,
        public readonly string $did,
        public readonly \DateTimeInterface $expiresAt,
        public readonly ?string $handle = null,
        public readonly ?string $issuer = null,
        public readonly array $scope = [],
        public readonly AuthType $authType = AuthType::OAuth,
    ) {}

    /**
     * Create from API response.
     *
     * Handles both legacy createSession format (accessJwt, refreshJwt, did)
     * and OAuth token format (access_token, refresh_token, sub).
     */
    public static function fromResponse(array $data, ?string $handle = null, ?string $issuer = null): self
    {
        // OAuth token endpoint format
        if (isset($data['access_token'])) {
            return new self(
                accessJwt: $data['access_token'],
                refreshJwt: $data['refresh_token'] ?? '',
                did: $data['sub'] ?? '',
                expiresAt: now()->addSeconds($data['expires_in'] ?? 300),
                handle: $handle,
                issuer: $issuer,
                scope: isset($data['scope']) ? explode(' ', $data['scope']) : [],
                authType: AuthType::OAuth,
            );
        }

        // Legacy createSession format (app passwords have full access)
        // Parse expiry from JWT since createSession doesn't return expiresIn
        $expiresAt = self::parseJwtExpiry($data['accessJwt']) ?? now()->addHour();

        return new self(
            accessJwt: $data['accessJwt'],
            refreshJwt: $data['refreshJwt'],
            did: $data['did'],
            expiresAt: $expiresAt,
            handle: $data['handle'] ?? $handle,
            issuer: $issuer,
            scope: ['atproto', 'transition:generic', 'transition:email'],
            authType: AuthType::Legacy,
        );
    }

    /**
     * Parse the expiry timestamp from a JWT's payload.
     */
    protected static function parseJwtExpiry(string $jwt): ?\DateTimeInterface
    {
        $parts = explode('.', $jwt);

        if (count($parts) !== 3) {
            return null;
        }

        $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);

        if (! isset($payload['exp'])) {
            return null;
        }

        return Carbon::createFromTimestamp($payload['exp']);
    }
}
