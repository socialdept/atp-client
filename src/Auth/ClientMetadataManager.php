<?php

namespace SocialDept\AtpClient\Auth;

class ClientMetadataManager
{
    /**
     * Get the client ID (typically the client URL)
     */
    public function getClientId(): string
    {
        return config('atp-client.client.url');
    }

    /**
     * Get the client metadata URL
     */
    public function getMetadataUrl(): ?string
    {
        return config('atp-client.client.metadata_url');
    }

    /**
     * Get the redirect URIs
     *
     * @return array<string>
     */
    public function getRedirectUris(): array
    {
        return config('atp-client.client.redirect_uris', []);
    }

    /**
     * Get the OAuth scopes
     *
     * @return array<string>
     */
    public function getScopes(): array
    {
        return config('atp-client.client.scopes', ['atproto', 'transition:generic']);
    }

    /**
     * Get the client metadata as an array
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'client_id' => $this->getClientId(),
            'client_name' => config('atp-client.client.name'),
            'client_uri' => config('atp-client.client.url'),
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
}
