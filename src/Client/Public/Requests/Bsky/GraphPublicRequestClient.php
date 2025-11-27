<?php

namespace SocialDept\AtpClient\Client\Public\Requests\Bsky;

use SocialDept\AtpClient\Client\Public\Requests\PublicRequest;
use SocialDept\AtpClient\Http\Response;

class GraphPublicRequestClient extends PublicRequest
{
    public function getFollowers(string $actor, int $limit = 50, ?string $cursor = null): Response
    {
        return $this->atp->client->get('app.bsky.graph.getFollowers', compact('actor', 'limit', 'cursor'));
    }

    public function getFollows(string $actor, int $limit = 50, ?string $cursor = null): Response
    {
        return $this->atp->client->get('app.bsky.graph.getFollows', compact('actor', 'limit', 'cursor'));
    }

    public function getKnownFollowers(string $actor, int $limit = 50, ?string $cursor = null): Response
    {
        return $this->atp->client->get('app.bsky.graph.getKnownFollowers', compact('actor', 'limit', 'cursor'));
    }

    public function getList(string $list, int $limit = 50, ?string $cursor = null): Response
    {
        return $this->atp->client->get('app.bsky.graph.getList', compact('list', 'limit', 'cursor'));
    }

    public function getLists(string $actor, int $limit = 50, ?string $cursor = null): Response
    {
        return $this->atp->client->get('app.bsky.graph.getLists', compact('actor', 'limit', 'cursor'));
    }

    public function getRelationships(string $actor, array $others = []): Response
    {
        return $this->atp->client->get('app.bsky.graph.getRelationships', compact('actor', 'others'));
    }

    public function getStarterPack(string $starterPack): Response
    {
        return $this->atp->client->get('app.bsky.graph.getStarterPack', compact('starterPack'));
    }

    public function getStarterPacks(array $uris): Response
    {
        return $this->atp->client->get('app.bsky.graph.getStarterPacks', compact('uris'));
    }

    public function getSuggestedFollowsByActor(string $actor): Response
    {
        return $this->atp->client->get('app.bsky.graph.getSuggestedFollowsByActor', compact('actor'));
    }
}
