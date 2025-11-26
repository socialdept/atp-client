<?php

namespace SocialDept\AtpClient\Session;

use SocialDept\AtpClient\Data\Credentials;
use SocialDept\AtpClient\Data\DPoPKey;
use SocialDept\AtpClient\Enums\AuthType;

class Session
{
    public function __construct(
        protected Credentials $credentials,
        protected DPoPKey $dpopKey,
        protected string $pdsEndpoint,
    ) {}

    public function did(): string
    {
        return $this->credentials->did;
    }

    public function handle(): ?string
    {
        return $this->credentials->handle;
    }

    public function accessToken(): string
    {
        return $this->credentials->accessToken;
    }

    public function refreshToken(): string
    {
        return $this->credentials->refreshToken;
    }

    public function dpopKey(): DPoPKey
    {
        return $this->dpopKey;
    }

    public function pdsEndpoint(): string
    {
        return $this->pdsEndpoint;
    }

    public function isExpired(): bool
    {
        return $this->credentials->isExpired();
    }

    public function expiresIn(): int
    {
        return $this->credentials->expiresIn();
    }

    public function scopes(): array
    {
        return $this->credentials->scope;
    }

    public function hasScope(string $scope): bool
    {
        return in_array($scope, $this->credentials->scope, true);
    }

    public function authType(): AuthType
    {
        return $this->credentials->authType;
    }

    public function isLegacy(): bool
    {
        return $this->credentials->authType === AuthType::Legacy;
    }

    public function withCredentials(Credentials $credentials): self
    {
        return new self($credentials, $this->dpopKey, $this->pdsEndpoint);
    }
}
