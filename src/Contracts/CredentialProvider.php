<?php

namespace SocialDept\AtpClient\Contracts;

use SocialDept\AtpClient\Data\AccessToken;
use SocialDept\AtpClient\Data\Credentials;

interface CredentialProvider
{
    /**
     * Get credentials for the given identifier
     */
    public function getCredentials(string $identifier): ?Credentials;

    /**
     * Store new credentials (initial OAuth or app password login)
     */
    public function storeCredentials(string $identifier, AccessToken $token): void;

    /**
     * Update credentials after token refresh
     * CRITICAL: Refresh tokens are single-use!
     */
    public function updateCredentials(string $identifier, AccessToken $token): void;

    /**
     * Remove credentials
     */
    public function removeCredentials(string $identifier): void;
}
