<?php

namespace SocialDept\AtpClient\Providers;

use Illuminate\Support\Facades\Cache;
use SocialDept\AtpClient\Contracts\CredentialProvider;
use SocialDept\AtpClient\Data\AccessToken;
use SocialDept\AtpClient\Data\Credentials;

class CacheCredentialProvider implements CredentialProvider
{
    protected string $prefix = 'atp:credentials:';

    public function getCredentials(string $did): ?Credentials
    {
        return Cache::get($this->key($did));
    }

    public function storeCredentials(string $did, AccessToken $token): void
    {
        Cache::put($this->key($did), $this->toCredentials($token));
    }

    public function updateCredentials(string $did, AccessToken $token): void
    {
        $this->storeCredentials($did, $token);
    }

    public function removeCredentials(string $did): void
    {
        Cache::forget($this->key($did));
    }

    protected function key(string $did): string
    {
        return $this->prefix.$did;
    }

    protected function toCredentials(AccessToken $token): Credentials
    {
        return new Credentials(
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
}
