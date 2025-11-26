<?php

namespace SocialDept\AtpClient\Client\Requests\Atproto;

use SocialDept\AtpClient\Client\Requests\Request;
use SocialDept\AtpClient\Http\Response;

class IdentityRequestClient extends Request
{
    /**
     * Resolve handle to DID
     *
     * @see https://docs.bsky.app/docs/api/com-atproto-identity-resolve-handle
     */
    public function resolveHandle(string $handle): Response
    {
        return $this->atp->client->get(
            endpoint: 'com.atproto.identity.resolveHandle',
            params: compact('handle')
        );
    }

    /**
     * Update handle
     *
     * @see https://docs.bsky.app/docs/api/com-atproto-identity-update-handle
     */
    public function updateHandle(string $handle): Response
    {
        return $this->atp->client->post(
            endpoint: 'com.atproto.identity.updateHandle',
            body: compact('handle')
        );
    }
}
