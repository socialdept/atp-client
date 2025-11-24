<?php

namespace SocialDept\AtpClient\Client\Requests\Atproto;

use SocialDept\AtpClient\Client\Requests\Request;
use SocialDept\AtpClient\Http\Response;

class Server extends Request
{
    /**
     * Get current session
     */
    public function getSession(): Response
    {
        return $this->atp->client->get(
            endpoint: 'com.atproto.server.getSession'
        );
    }

    /**
     * Describe server
     */
    public function describeServer(): Response
    {
        return $this->atp->client->get(
            endpoint: 'com.atproto.server.describeServer'
        );
    }
}
