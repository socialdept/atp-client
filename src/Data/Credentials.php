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
    ) {
    }

    public function isExpired(): bool
    {
        return now()->isAfter($this->expiresAt);
    }

    public function expiresIn(): int
    {
        // Carbon 3 diffInSeconds returns a float; cast explicitly so PHP 8.4
        // doesn't warn on the lossy implicit float->int narrowing.
        return (int) now()->diffInSeconds($this->expiresAt, false);
    }
}
