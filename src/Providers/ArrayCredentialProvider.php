<?php

namespace SocialDept\AtpClient\Providers;

use SocialDept\AtpClient\Contracts\CredentialProvider;
use SocialDept\AtpClient\Data\AccessToken;
use SocialDept\AtpClient\Data\Credentials;

class ArrayCredentialProvider implements CredentialProvider
{
    /**
     * In-memory credential storage.
     *
     * @var array<string, Credentials>
     */
    protected array $credentials = [];

    public function getCredentials(string $identifier): ?Credentials
    {
        return $this->credentials[$identifier] ?? null;
    }

    public function storeCredentials(string $identifier, AccessToken $token): void
    {
        $this->credentials[$identifier] = new Credentials(
            identifier: $identifier,
            did: $token->did,
            accessToken: $token->accessJwt,
            refreshToken: $token->refreshJwt,
            expiresAt: $token->expiresAt,
            handle: $token->handle,
            issuer: $token->issuer,
        );
    }

    public function updateCredentials(string $identifier, AccessToken $token): void
    {
        $this->storeCredentials($identifier, $token);
    }

    public function removeCredentials(string $identifier): void
    {
        unset($this->credentials[$identifier]);
    }
}
