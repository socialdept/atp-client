<?php

namespace SocialDept\AtpClient\Client\Public\Requests\Atproto;

use SocialDept\AtpClient\Client\Public\Requests\PublicRequest;
use SocialDept\AtpClient\Http\Response;

class IdentityPublicRequestClient extends PublicRequest
{
    public function resolveHandle(string $handle): Response
    {
        return $this->atp->client->get('com.atproto.identity.resolveHandle', compact('handle'));
    }
}
