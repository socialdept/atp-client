<?php

namespace SocialDept\AtpClient\Data;

use SocialDept\AtpClient\Enums\AuthType;

class Credentials
{
    public function __construct(
        public readonly string $did,
        public readonly string $accessToken,
        public readonly string $refreshToken,
        public readonly \DateTimeInterface $expiresAt,
        public readonly ?string $handle = null,
        public readonly ?string $issuer = null,
        public readonly array $scope = [],
        public readonly AuthType $authType = AuthType::OAuth,
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
