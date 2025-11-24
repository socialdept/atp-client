<?php

namespace SocialDept\AtpClient\Client\Requests\Atproto;

use SocialDept\AtpClient\Client\Requests\Request;
use SocialDept\AtpClient\Http\Response;

class Identity extends Request
{
    /**
     * Resolve handle to DID
     */
    public function resolveHandle(string $handle): Response
    {
        return $this->atp->client->get('com.atproto.identity.resolveHandle', compact('handle'));
    }

    /**
     * Update handle
     */
    public function updateHandle(string $handle): Response
    {
        return $this->atp->client->post('com.atproto.identity.updateHandle', compact('handle'));
    }
}
