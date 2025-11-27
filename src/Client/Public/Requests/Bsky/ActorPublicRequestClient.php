<?php

namespace SocialDept\AtpClient\Client\Public\Requests\Bsky;

use SocialDept\AtpClient\Client\Public\Requests\PublicRequest;
use SocialDept\AtpClient\Http\Response;

class ActorPublicRequestClient extends PublicRequest
{
    public function getProfile(string $actor): Response
    {
        return $this->atp->client->get('app.bsky.actor.getProfile', compact('actor'));
    }

    public function getProfiles(array $actors): Response
    {
        return $this->atp->client->get('app.bsky.actor.getProfiles', compact('actors'));
    }

    public function getSuggestions(int $limit = 50, ?string $cursor = null): Response
    {
        return $this->atp->client->get('app.bsky.actor.getSuggestions', compact('limit', 'cursor'));
    }

    public function searchActors(string $q, int $limit = 25, ?string $cursor = null): Response
    {
        return $this->atp->client->get('app.bsky.actor.searchActors', compact('q', 'limit', 'cursor'));
    }

    public function searchActorsTypeahead(string $q, int $limit = 10): Response
    {
        return $this->atp->client->get('app.bsky.actor.searchActorsTypeahead', compact('q', 'limit'));
    }
}
