<?php

namespace SocialDept\AtpClient\Client\Requests\Atproto;

use SocialDept\AtpClient\Attributes\RequiresScope;
use SocialDept\AtpClient\Client\Requests\Request;
use SocialDept\AtpClient\Enums\Scope;

class IdentityRequestClient extends Request
{
    /**
     * Resolve handle to DID
     *
     * @requires transition:generic (rpc:com.atproto.identity.resolveHandle)
     *
     * @see https://docs.bsky.app/docs/api/com-atproto-identity-resolve-handle
     */
    #[RequiresScope(Scope::TransitionGeneric, granular: 'rpc:com.atproto.identity.resolveHandle')]
    public function resolveHandle(string $handle): string
    {
        $response = $this->atp->client->get(
            endpoint: 'com.atproto.identity.resolveHandle',
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
    #[RequiresScope(Scope::Atproto, granular: 'identity:handle')]
    public function updateHandle(string $handle): void
    {
        $this->atp->client->post(
            endpoint: 'com.atproto.identity.updateHandle',
            body: compact('handle')
        );
    }
}
