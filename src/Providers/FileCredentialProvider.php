<?php

namespace SocialDept\AtpClient\Providers;

use SocialDept\AtpClient\Contracts\CredentialProvider;
use SocialDept\AtpClient\Data\AccessToken;
use SocialDept\AtpClient\Data\Credentials;

class FileCredentialProvider implements CredentialProvider
{
    protected string $storagePath;

    public function __construct(?string $storagePath = null)
    {
        $this->storagePath = $storagePath ?? storage_path('app/atp-credentials');

        if (! is_dir($this->storagePath)) {
            mkdir($this->storagePath, 0755, true);
        }
    }

    public function getCredentials(string $did): ?Credentials
    {
        $path = $this->path($did);

        if (! file_exists($path)) {
            return null;
        }

        return unserialize(file_get_contents($path));
    }

    public function storeCredentials(string $did, AccessToken $token): void
    {
        file_put_contents($this->path($did), serialize($this->toCredentials($token)));
    }

    public function updateCredentials(string $did, AccessToken $token): void
    {
        $this->storeCredentials($did, $token);
    }

    public function removeCredentials(string $did): void
    {
        $path = $this->path($did);

        if (file_exists($path)) {
            unlink($path);
        }
    }

    protected function path(string $did): string
    {
        return $this->storagePath.'/'.hash('sha256', $did).'.cred';
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
