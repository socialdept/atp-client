<?php

namespace SocialDept\AtpClient\Auth;

/**
 * Manages OAuth client metadata for AT Protocol authentication.
 *
 * The client_id in atproto OAuth is a URL that serves as both the unique
 * identifier and the location of the client metadata document.
 *
 * For production: Use an HTTPS URL pointing to your client metadata.
 * For localhost: Use exactly 'http://localhost' (no port).
 *
 * @see https://atproto.com/specs/oauth#clients
 */
class ClientMetadataManager
{
    /**
     * Get the client ID (URL to client metadata document).
     *
     * For production clients, this is an HTTPS URL like:
     * 'https://example.com/oauth/client-metadata.json'
     *
     * For localhost development, this must be exactly 'http://localhost'
     * (no port number allowed per atproto spec).
     */
    public function getClientId(): string
    {
        $clientId = config('client.client.client_id');

        if ($clientId) {
            return $clientId;
        }

        // Fall back to auto-generated client_id based on app URL
        return $this->generateClientId();
    }

    /**
     * Check if this is a localhost development client.
     */
    public function isLocalhost(): bool
    {
        return $this->getClientId() === 'http://localhost';
    }

    /**
     * Get the redirect URIs.
     *
     * For localhost development, redirect URIs must use 127.0.0.1
     * (not localhost) and can include a port number.
     *
     * @return array<string>
     */
    public function getRedirectUris(): array
    {
        $uris = config('client.client.redirect_uris', []);

        if (! empty($uris)) {
            return $uris;
        }

        // Default redirect URI based on environment
        if ($this->isLocalhost()) {
            // For localhost, use 127.0.0.1
            return ['http://127.0.0.1'];
        }

        // For production, use app URL
        return [config('client.client.url').'/auth/atp/callback'];
    }

    /**
     * Get the OAuth scopes.
     *
     * @return array<string>
     */
    public function getScopes(): array
    {
        return config('client.client.scopes', ['atproto', 'transition:generic']);
    }

    /**
     * Get the client metadata as an array.
     *
     * This is the structure served at the client_id URL.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'client_id' => $this->getClientId(),
            'client_name' => config('client.client.name'),
            'client_uri' => config('client.client.url'),
            'redirect_uris' => $this->getRedirectUris(),
            'scope' => implode(' ', $this->getScopes()),
            'grant_types' => [
                'authorization_code',
                'refresh_token',
            ],
            'response_types' => ['code'],
            'token_endpoint_auth_method' => 'none',
            'application_type' => 'web',
            'dpop_bound_access_tokens' => true,
        ];
    }

    /**
     * Generate client_id from app configuration.
     *
     * In production, points to the package's client-metadata.json endpoint.
     * For localhost detection, checks if app URL contains localhost or .test.
     */
    protected function generateClientId(): string
    {
        $appUrl = config('client.client.url') ?? config('app.url');
        $host = parse_url($appUrl, PHP_URL_HOST);

        // Detect local development environments
        if ($this->isLocalDevelopment($host)) {
            return 'http://localhost';
        }

        // Production: point to client metadata endpoint
        $prefix = config('client.oauth.prefix', '/atp/oauth/');

        return rtrim($appUrl, '/').rtrim($prefix, '/').'/client-metadata.json';
    }

    /**
     * Check if the host indicates a local development environment.
     */
    protected function isLocalDevelopment(?string $host): bool
    {
        if (! $host) {
            return false;
        }

        return $host === 'localhost'
            || $host === '127.0.0.1'
            || str_ends_with($host, '.localhost')
            || str_ends_with($host, '.test')
            || str_ends_with($host, '.local');
    }
}
