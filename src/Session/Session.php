<?php

namespace SocialDept\AtpClient\Session;

use SocialDept\AtpClient\Data\Credentials;
use SocialDept\AtpClient\Data\DPoPKey;
use SocialDept\AtpClient\Enums\AuthType;
use SocialDept\AtpClient\Enums\Scope;

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

    /**
     * Get the authorization server for OAuth operations.
     *
     * For OAuth sessions, this returns the stored issuer (e.g., bsky.social).
     * For legacy sessions, this returns the PDS endpoint.
     */
    public function authServer(): string
    {
        return $this->credentials->issuer ?? $this->pdsEndpoint;
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

    /**
     * Check if the session has the given scope (alias for hasScope with Scope enum support).
     */
    public function can(string|Scope $scope): bool
    {
        $scopeValue = $scope instanceof Scope ? $scope->value : $scope;

        return $this->hasScope($scopeValue);
    }

    /**
     * Check if the session has any of the given scopes.
     *
     * @param  array<string|Scope>  $scopes
     */
    public function canAny(array $scopes): bool
    {
        foreach ($scopes as $scope) {
            if ($this->can($scope)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the session has all of the given scopes.
     *
     * @param  array<string|Scope>  $scopes
     */
    public function canAll(array $scopes): bool
    {
        foreach ($scopes as $scope) {
            if (! $this->can($scope)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if the session does NOT have the given scope.
     */
    public function cannot(string|Scope $scope): bool
    {
        return ! $this->can($scope);
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
