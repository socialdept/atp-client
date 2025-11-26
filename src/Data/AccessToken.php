<?php

namespace SocialDept\AtpClient\Data;

class AccessToken
{
    public function __construct(
        public readonly string $accessJwt,
        public readonly string $refreshJwt,
        public readonly string $did,
        public readonly \DateTimeInterface $expiresAt,
        public readonly ?string $handle = null,
        public readonly ?string $issuer = null,
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
            );
        }

        // Legacy createSession format
        return new self(
            accessJwt: $data['accessJwt'],
            refreshJwt: $data['refreshJwt'],
            did: $data['did'],
            expiresAt: now()->addSeconds($data['expiresIn'] ?? 300),
            handle: $data['handle'] ?? $handle,
            issuer: $issuer,
        );
    }
}
