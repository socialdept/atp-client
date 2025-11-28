<?php

namespace SocialDept\AtpClient\Client\Public\Requests\Bsky;

use SocialDept\AtpClient\Client\Public\Requests\PublicRequest;
use SocialDept\AtpClient\Enums\Nsid\BskyGraph;
use SocialDept\AtpClient\Data\Responses\Bsky\Graph\GetFollowersResponse;
use SocialDept\AtpClient\Data\Responses\Bsky\Graph\GetFollowsResponse;
use SocialDept\AtpClient\Data\Responses\Bsky\Graph\GetKnownFollowersResponse;
use SocialDept\AtpClient\Data\Responses\Bsky\Graph\GetListResponse;
use SocialDept\AtpClient\Data\Responses\Bsky\Graph\GetListsResponse;
use SocialDept\AtpClient\Data\Responses\Bsky\Graph\GetRelationshipsResponse;
use SocialDept\AtpClient\Data\Responses\Bsky\Graph\GetStarterPacksResponse;
use SocialDept\AtpClient\Data\Responses\Bsky\Graph\GetSuggestedFollowsByActorResponse;
use SocialDept\AtpSchema\Generated\App\Bsky\Graph\Defs\StarterPackView;

class GraphPublicRequestClient extends PublicRequest
{
    public function getFollowers(string $actor, int $limit = 50, ?string $cursor = null): GetFollowersResponse
    {
        $response = $this->atp->client->get(
            endpoint: BskyGraph::GetFollowers,
            params: compact('actor', 'limit', 'cursor')
        );

        return GetFollowersResponse::fromArray($response->json());
    }

    public function getFollows(string $actor, int $limit = 50, ?string $cursor = null): GetFollowsResponse
    {
        $response = $this->atp->client->get(
            endpoint: BskyGraph::GetFollows,
            params: compact('actor', 'limit', 'cursor')
        );

        return GetFollowsResponse::fromArray($response->json());
    }

    public function getKnownFollowers(string $actor, int $limit = 50, ?string $cursor = null): GetKnownFollowersResponse
    {
        $response = $this->atp->client->get(
            endpoint: BskyGraph::GetKnownFollowers,
            params: compact('actor', 'limit', 'cursor')
        );

        return GetKnownFollowersResponse::fromArray($response->json());
    }

    public function getList(string $list, int $limit = 50, ?string $cursor = null): GetListResponse
    {
        $response = $this->atp->client->get(
            endpoint: BskyGraph::GetList,
            params: compact('list', 'limit', 'cursor')
        );

        return GetListResponse::fromArray($response->json());
    }

    public function getLists(string $actor, int $limit = 50, ?string $cursor = null): GetListsResponse
    {
        $response = $this->atp->client->get(
            endpoint: BskyGraph::GetLists,
            params: compact('actor', 'limit', 'cursor')
        );

        return GetListsResponse::fromArray($response->json());
    }

    public function getRelationships(string $actor, array $others = []): GetRelationshipsResponse
    {
        $response = $this->atp->client->get(
            endpoint: BskyGraph::GetRelationships,
            params: compact('actor', 'others')
        );

        return GetRelationshipsResponse::fromArray($response->json());
    }

    public function getStarterPack(string $starterPack): StarterPackView
    {
        $response = $this->atp->client->get(
            endpoint: BskyGraph::GetStarterPack,
            params: compact('starterPack')
        );

        return StarterPackView::fromArray($response->json()['starterPack']);
    }

    public function getStarterPacks(array $uris): GetStarterPacksResponse
    {
        $response = $this->atp->client->get(
            endpoint: BskyGraph::GetStarterPacks,
            params: compact('uris')
        );

        return GetStarterPacksResponse::fromArray($response->json());
    }

    public function getSuggestedFollowsByActor(string $actor): GetSuggestedFollowsByActorResponse
    {
        $response = $this->atp->client->get(
            endpoint: BskyGraph::GetSuggestedFollowsByActor,
            params: compact('actor')
        );

        return GetSuggestedFollowsByActorResponse::fromArray($response->json());
    }
}
