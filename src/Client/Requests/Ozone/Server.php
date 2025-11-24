<?php

namespace SocialDept\AtpClient\Client\Requests\Ozone;

use SocialDept\AtpClient\Client\Requests\Request;
use SocialDept\AtpClient\Http\Response;

class Server extends Request
{
    /**
     * Get blob
     */
    public function getBlob(string $did, string $cid): Response
    {
        return $this->atp->client->get('tools.ozone.server.getBlob', compact('did', 'cid'));
    }

    /**
     * Get config
     */
    public function getConfig(): Response
    {
        return $this->atp->client->get('tools.ozone.server.getConfig');
    }
}
