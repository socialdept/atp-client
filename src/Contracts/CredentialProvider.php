<?php

namespace SocialDept\AtpClient\Contracts;

use SocialDept\AtpClient\Data\AccessToken;
use SocialDept\AtpClient\Data\Credentials;

interface CredentialProvider
{
    /**
     * Get credentials for the given DID
     */
    public function getCredentials(string $did): ?Credentials;

    /**
     * Store new credentials (initial OAuth or app password login)
     */
    public function storeCredentials(string $did, AccessToken $token): void;

    /**
     * Update credentials after token refresh
     * CRITICAL: Refresh tokens are single-use!
     */
    public function updateCredentials(string $did, AccessToken $token): void;

    /**
     * Remove credentials
     */
    public function removeCredentials(string $did): void;
}
