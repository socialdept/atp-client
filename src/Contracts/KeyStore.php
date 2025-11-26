<?php

namespace SocialDept\AtpClient\Contracts;

use SocialDept\AtpClient\Data\DPoPKey;

interface KeyStore
{
    /**
     * Store DPoP key for session
     */
    public function store(string $sessionId, DPoPKey $key): void;

    /**
     * Retrieve DPoP key
     */
    public function get(string $sessionId): ?DPoPKey;

    /**
     * Delete DPoP key
     */
    public function delete(string $sessionId): void;

    /**
     * Check if key exists
     */
    public function exists(string $sessionId): bool;
}
