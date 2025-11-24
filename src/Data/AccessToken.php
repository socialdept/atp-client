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
    ) {}

    public static function fromResponse(array $data): self
    {
        return new self(
            accessJwt: $data['accessJwt'],
            refreshJwt: $data['refreshJwt'],
            did: $data['did'],
            expiresAt: now()->addSeconds($data['expiresIn'] ?? 300),
            handle: $data['handle'] ?? null,
        );
    }
}
