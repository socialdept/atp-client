<?php

namespace SocialDept\AtpClient\Auth;

use Illuminate\Support\Facades\Cache;

class DPoPNonceManager
{
    /**
     * Get DPoP nonce for PDS endpoint
     *
     * Returns cached nonce if available, otherwise empty string.
     * The first request will fail with use_dpop_nonce error,
     * and the server will provide a valid nonce in the response.
     */
    public function getNonce(string $pdsEndpoint): string
    {
        $cacheKey = 'dpop_nonce:'.md5($pdsEndpoint);

        // Return cached nonce if available, empty string otherwise
        // Empty nonce triggers use_dpop_nonce error, which is expected
        return Cache::get($cacheKey, '');
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
}
