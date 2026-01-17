<?php

namespace SocialDept\AtpClient\Auth;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use SocialDept\AtpClient\Data\AuthorizationServerMetadata;

class AuthorizationServerDiscovery
{
    protected const BSKY_AUTH_SERVER = 'https://bsky.social';

    /**
     * Discover the authorization server for a given PDS.
     *
     * Per the AT Protocol OAuth specification, clients MUST discover the
     * authorization server dynamically rather than assuming the PDS is
     * the authorization server.
     *
     * @see https://atproto.com/specs/oauth
     */
    public function discover(string $pdsEndpoint): AuthorizationServerMetadata
    {
        // Fast path: All Bluesky-hosted PDSes (*.bsky.network) use bsky.social
        if ($this->isBlueskyHostedPds($pdsEndpoint)) {
            return $this->getAuthorizationServerMetadata(self::BSKY_AUTH_SERVER, $pdsEndpoint);
        }

        $authServerUrl = $this->getAuthorizationServerUrl($pdsEndpoint);

        return $this->getAuthorizationServerMetadata($authServerUrl, $pdsEndpoint);
    }

    /**
     * Check if the PDS is hosted by Bluesky (mushroom PDSes).
     */
    protected function isBlueskyHostedPds(string $pdsEndpoint): bool
    {
        $host = parse_url($pdsEndpoint, PHP_URL_HOST);

        return $host && str_ends_with($host, '.bsky.network');
    }

    /**
     * Fetch protected resource metadata from PDS to get authorization server URL.
     *
     * @see https://datatracker.ietf.org/doc/html/rfc9449
     */
    protected function getAuthorizationServerUrl(string $pdsEndpoint): string
    {
        $cacheKey = 'atp_oauth_resource:'.hash('sha256', $pdsEndpoint);

        return Cache::remember($cacheKey, now()->addHours(24), function () use ($pdsEndpoint) {
            $url = rtrim($pdsEndpoint, '/').'/.well-known/oauth-protected-resource';

            $response = Http::acceptJson()
                ->timeout(10)
                ->get($url);

            if ($response->failed()) {
                // Fallback: assume PDS is the auth server (self-hosted PDS pattern)
                return $pdsEndpoint;
            }

            $data = $response->json();
            $servers = $data['authorization_servers'] ?? [];

            if (empty($servers)) {
                // Fallback: assume PDS is the auth server
                return $pdsEndpoint;
            }

            return $servers[0];
        });
    }

    /**
     * Fetch authorization server metadata.
     *
     * @see https://datatracker.ietf.org/doc/html/rfc8414
     */
    protected function getAuthorizationServerMetadata(string $authServerUrl, string $pdsEndpoint): AuthorizationServerMetadata
    {
        $cacheKey = 'atp_oauth_as_metadata:'.hash('sha256', $authServerUrl);

        $metadata = Cache::remember($cacheKey, now()->addHours(24), function () use ($authServerUrl, $pdsEndpoint) {
            $url = rtrim($authServerUrl, '/').'/.well-known/oauth-authorization-server';

            $response = Http::acceptJson()
                ->timeout(10)
                ->get($url);

            if ($response->failed()) {
                // Fallback: construct default endpoints (self-hosted PDS pattern)
                return [
                    'issuer' => $pdsEndpoint,
                    'authorization_endpoint' => $pdsEndpoint.'/oauth/authorize',
                    'token_endpoint' => $pdsEndpoint.'/oauth/token',
                    'pushed_authorization_request_endpoint' => $pdsEndpoint.'/oauth/par',
                    'revocation_endpoint' => $pdsEndpoint.'/oauth/revoke',
                ];
            }

            return $response->json();
        });

        return new AuthorizationServerMetadata(
            issuer: $metadata['issuer'],
            authorizationEndpoint: $metadata['authorization_endpoint'],
            tokenEndpoint: $metadata['token_endpoint'],
            parEndpoint: $metadata['pushed_authorization_request_endpoint'],
            pdsEndpoint: $pdsEndpoint,
            revocationEndpoint: $metadata['revocation_endpoint'] ?? null,
            introspectionEndpoint: $metadata['introspection_endpoint'] ?? null,
        );
    }

    /**
     * Clear cached discovery data for a PDS.
     */
    public function clearCache(string $pdsEndpoint): void
    {
        Cache::forget('atp_oauth_resource:'.hash('sha256', $pdsEndpoint));

        // Use fast path for Bluesky-hosted PDSes
        $authServerUrl = $this->isBlueskyHostedPds($pdsEndpoint)
            ? self::BSKY_AUTH_SERVER
            : $pdsEndpoint;

        Cache::forget('atp_oauth_as_metadata:'.hash('sha256', $authServerUrl));
    }
}
