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

    public function getCredentials(string $did): ?Credentials
    {
        return $this->credentials[$did] ?? null;
    }

    public function storeCredentials(string $did, AccessToken $token): void
    {
        $this->credentials[$did] = new Credentials(
            did: $token->did,
            accessToken: $token->accessJwt,
            refreshToken: $token->refreshJwt,
            expiresAt: $token->expiresAt,
            handle: $token->handle,
            issuer: $token->issuer,
            scope: $token->scope,
            authType: $token->authType,
        );
    }

    public function updateCredentials(string $did, AccessToken $token): void
    {
        $this->storeCredentials($did, $token);
    }

    public function removeCredentials(string $did): void
    {
        unset($this->credentials[$did]);
    }
}
