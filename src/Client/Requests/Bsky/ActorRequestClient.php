<?php

namespace SocialDept\AtpClient\Client\Requests\Bsky;

use SocialDept\AtpClient\Attributes\RequiresScope;
use SocialDept\AtpClient\Client\Requests\Request;
use SocialDept\AtpClient\Enums\Nsid\BskyActor;
use SocialDept\AtpClient\Enums\Scope;
use SocialDept\AtpSchema\Generated\App\Bsky\Actor\Defs\ProfileViewDetailed;

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
    public function getProfile(string $actor): ProfileViewDetailed
    {
        $response = $this->atp->client->get(
            endpoint: BskyActor::GetProfile,
            params: compact('actor')
        );

        return ProfileViewDetailed::fromArray($response->json());
    }
}
