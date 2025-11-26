<?php

namespace SocialDept\AtpClient\Data;

class Credentials
{
    public function __construct(
        public readonly string $identifier,
        public readonly string $did,
        public readonly string $accessToken,
        public readonly string $refreshToken,
        public readonly \DateTimeInterface $expiresAt,
    ) {}

    public function isExpired(): bool
    {
        return now()->isAfter($this->expiresAt);
    }

    public function expiresIn(): int
    {
        return now()->diffInSeconds($this->expiresAt, false);
    }
}
