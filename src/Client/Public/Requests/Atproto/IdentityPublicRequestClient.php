<?php

namespace SocialDept\AtpClient\Client\Public\Requests\Atproto;

use SocialDept\AtpClient\Client\Public\Requests\PublicRequest;
use SocialDept\AtpClient\Enums\Nsid\AtprotoIdentity;

class IdentityPublicRequestClient extends PublicRequest
{
    public function resolveHandle(string $handle): string
    {
        $response = $this->atp->client->get(
            endpoint: AtprotoIdentity::ResolveHandle,
            params: compact('handle')
        );

        return $response->json()['did'];
    }
}
