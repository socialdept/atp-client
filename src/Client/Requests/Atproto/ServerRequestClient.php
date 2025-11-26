<?php

namespace SocialDept\AtpClient\Client\Requests\Atproto;

use SocialDept\AtpClient\Client\Requests\Request;
use SocialDept\AtpClient\Http\Response;

class ServerRequestClient extends Request
{
    /**
     * Get current session
     *
     * @see https://docs.bsky.app/docs/api/com-atproto-server-get-session
     */
    public function getSession(): Response
    {
        return $this->atp->client->get(
            endpoint: 'com.atproto.server.getSession'
        );
    }

    /**
     * Describe server
     *
     * @see https://docs.bsky.app/docs/api/com-atproto-server-describe-server
     */
    public function describeServer(): Response
    {
        return $this->atp->client->get(
            endpoint: 'com.atproto.server.describeServer'
        );
    }
}
