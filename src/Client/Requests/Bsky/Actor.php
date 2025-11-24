<?php

namespace SocialDept\AtpClient\Client\Requests\Bsky;

use SocialDept\AtpClient\Client\Requests\Request;
use SocialDept\AtpClient\Http\Response;

class Actor extends Request
{
    /**
     * Get actor profile
     */
    public function getProfile(string $actor): Response
    {
        return $this->atp->client->get('app.bsky.actor.getProfile', compact('actor'));
    }
}
