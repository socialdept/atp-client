<?php

namespace SocialDept\AtpClient\Client\Requests\Bsky;

use SocialDept\AtpClient\Attributes\RequiresScope;
use SocialDept\AtpClient\Client\Requests\Request;
use SocialDept\AtpClient\Enums\Scope;
use SocialDept\AtpClient\Http\Response;

class ActorRequestClient extends Request
{
    /**
     * Get actor profile
     *
     * @requires transition:generic (rpc:app.bsky.actor.getProfile)
     *
     * @see https://docs.bsky.app/docs/api/app-bsky-actor-get-profile
     */
    #[RequiresScope(Scope::TransitionGeneric, granular: 'rpc:app.bsky.actor.getProfile')]
    public function getProfile(string $actor): Response
    {
        return $this->atp->client->get(
            endpoint: 'app.bsky.actor.getProfile',
            params: compact('actor')
        );
    }
}
