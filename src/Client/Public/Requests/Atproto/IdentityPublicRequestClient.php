<?php

namespace SocialDept\AtpClient\Client\Public\Requests\Atproto;

use SocialDept\AtpClient\Client\Public\Requests\PublicRequest;

class IdentityPublicRequestClient extends PublicRequest
{
    public function resolveHandle(string $handle): string
    {
        $response = $this->atp->client->get('com.atproto.identity.resolveHandle', compact('handle'));

        return $response->json()['did'];
    }
}
