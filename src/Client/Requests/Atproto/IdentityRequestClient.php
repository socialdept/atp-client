<?php

namespace SocialDept\AtpClient\Client\Requests\Atproto;

use SocialDept\AtpClient\Attributes\PublicEndpoint;
use SocialDept\AtpClient\Attributes\ScopedEndpoint;
use SocialDept\AtpClient\Client\Requests\Request;
use SocialDept\AtpClient\Enums\Nsid\AtprotoIdentity;
use SocialDept\AtpClient\Enums\Scope;

class IdentityRequestClient extends Request
{
    /**
     * Resolve handle to DID
     *
     * @see https://docs.bsky.app/docs/api/com-atproto-identity-resolve-handle
     */
    #[PublicEndpoint]
    public function resolveHandle(string $handle): string
    {
        $response = $this->atp->client->get(
            endpoint: AtprotoIdentity::ResolveHandle,
            params: compact('handle')
        );

        return $response->json()['did'];
    }

    /**
     * Update handle
     *
     * @requires atproto (identity:handle)
     *
     * @see https://docs.bsky.app/docs/api/com-atproto-identity-update-handle
     */
    #[ScopedEndpoint(Scope::Atproto, granular: 'identity:handle')]
    public function updateHandle(string $handle): void
    {
        $this->atp->client->post(
            endpoint: AtprotoIdentity::UpdateHandle,
            body: compact('handle')
        );
    }
}
