<?php

namespace SocialDept\AtpClient\Auth;

use Illuminate\Support\Facades\Cache;

class DPoPNonceManager
{
    /**
     * Get DPoP nonce for PDS endpoint
     */
    public function getNonce(string $pdsEndpoint): string
    {
        $cacheKey = 'dpop_nonce:'.md5($pdsEndpoint);

        // Return cached nonce if available
        if ($nonce = Cache::get($cacheKey)) {
            return $nonce;
        }

        // Fetch new nonce from server
        $nonce = $this->fetchNonce($pdsEndpoint);

        // Cache for 5 minutes
        Cache::put($cacheKey, $nonce, now()->addMinutes(5));

        return $nonce;
    }

    /**
     * Store nonce returned from server response
     */
    public function storeNonce(string $pdsEndpoint, string $nonce): void
    {
        $cacheKey = 'dpop_nonce:'.md5($pdsEndpoint);
        Cache::put($cacheKey, $nonce, now()->addMinutes(5));
    }

    /**
     * Clear cached nonce (e.g., after nonce error)
     */
    public function clearNonce(string $pdsEndpoint): void
    {
        $cacheKey = 'dpop_nonce:'.md5($pdsEndpoint);
        Cache::forget($cacheKey);
    }

    /**
     * Fetch nonce from PDS server
     */
    protected function fetchNonce(string $pdsEndpoint): string
    {
        // Make a HEAD request to get initial nonce
        // The server returns nonce in DPoP-Nonce header
        try {
            $response = app('http')->head($pdsEndpoint.'/xrpc/_health');

            return $response->header('DPoP-Nonce') ?? 'fallback-nonce-'.time();
        } catch (\Exception $e) {
            // Fallback if health endpoint fails
            return 'fallback-nonce-'.time();
        }
    }
}
