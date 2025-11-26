<?php

namespace SocialDept\AtpClient\Client\Requests\Bsky;

use SocialDept\AtpClient\Client\Requests\Request;
use SocialDept\AtpClient\Http\Response;

class ActorRequestClient extends Request
{
    /**
     * Get actor profile
     *
     * @see https://docs.bsky.app/docs/api/app-bsky-actor-get-profile
     */
    public function getProfile(string $actor): Response
    {
        return $this->atp->client->get(
            endpoint: 'app.bsky.actor.getProfile',
            params: compact('actor')
        );
    }
}
