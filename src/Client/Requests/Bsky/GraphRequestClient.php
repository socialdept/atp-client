<?php

namespace SocialDept\AtpClient\Client\Requests\Bsky;

use SocialDept\AtpClient\Client\Requests\Request;
use SocialDept\AtpClient\Data\Responses\Bsky\Graph\GetFollowersResponse;
use SocialDept\AtpClient\Data\Responses\Bsky\Graph\GetFollowsResponse;
use SocialDept\AtpClient\Data\Responses\Bsky\Graph\GetKnownFollowersResponse;
use SocialDept\AtpClient\Data\Responses\Bsky\Graph\GetListResponse;
use SocialDept\AtpClient\Data\Responses\Bsky\Graph\GetListsResponse;
use SocialDept\AtpClient\Data\Responses\Bsky\Graph\GetRelationshipsResponse;
use SocialDept\AtpClient\Data\Responses\Bsky\Graph\GetStarterPacksResponse;
use SocialDept\AtpClient\Data\Responses\Bsky\Graph\GetSuggestedFollowsByActorResponse;
use SocialDept\AtpClient\Enums\Nsid\BskyGraph;
use SocialDept\AtpSchema\Generated\App\Bsky\Graph\Defs\StarterPackView;

class GraphRequestClient extends Request
{
    /**
     * Get followers of an actor
     *
     * @see https://docs.bsky.app/docs/api/app-bsky-graph-get-followers
     */
    public function getFollowers(string $actor, int $limit = 50, ?string $cursor = null): GetFollowersResponse
    {
        $response = $this->atp->client->get(
            endpoint: BskyGraph::GetFollowers,
            params: compact('actor', 'limit', 'cursor')
        );

        return GetFollowersResponse::fromArray($response->json());
    }

    /**
     * Get accounts that an actor follows
     *
     * @see https://docs.bsky.app/docs/api/app-bsky-graph-get-follows
     */
    public function getFollows(string $actor, int $limit = 50, ?string $cursor = null): GetFollowsResponse
    {
        $response = $this->atp->client->get(
            endpoint: BskyGraph::GetFollows,
            params: compact('actor', 'limit', 'cursor')
        );

        return GetFollowsResponse::fromArray($response->json());
    }

    /**
     * Get followers of an actor that you also follow
     *
     * @see https://docs.bsky.app/docs/api/app-bsky-graph-get-known-followers
     */
    public function getKnownFollowers(string $actor, int $limit = 50, ?string $cursor = null): GetKnownFollowersResponse
    {
        $response = $this->atp->client->get(
            endpoint: BskyGraph::GetKnownFollowers,
            params: compact('actor', 'limit', 'cursor')
        );

        return GetKnownFollowersResponse::fromArray($response->json());
    }

    /**
     * Get a list by URI
     *
     * @see https://docs.bsky.app/docs/api/app-bsky-graph-get-list
     */
    public function getList(string $list, int $limit = 50, ?string $cursor = null): GetListResponse
    {
        $response = $this->atp->client->get(
            endpoint: BskyGraph::GetList,
            params: compact('list', 'limit', 'cursor')
        );

        return GetListResponse::fromArray($response->json());
    }

    /**
     * Get lists created by an actor
     *
     * @see https://docs.bsky.app/docs/api/app-bsky-graph-get-lists
     */
    public function getLists(string $actor, int $limit = 50, ?string $cursor = null): GetListsResponse
    {
        $response = $this->atp->client->get(
            endpoint: BskyGraph::GetLists,
            params: compact('actor', 'limit', 'cursor')
        );

        return GetListsResponse::fromArray($response->json());
    }

    /**
     * Get relationships between actors
     *
     * @see https://docs.bsky.app/docs/api/app-bsky-graph-get-relationships
     */
    public function getRelationships(string $actor, array $others = []): GetRelationshipsResponse
    {
        $response = $this->atp->client->get(
            endpoint: BskyGraph::GetRelationships,
            params: compact('actor', 'others')
        );

        return GetRelationshipsResponse::fromArray($response->json());
    }

    /**
     * Get a starter pack by URI
     *
     * @see https://docs.bsky.app/docs/api/app-bsky-graph-get-starter-pack
     */
    public function getStarterPack(string $starterPack): StarterPackView
    {
        $response = $this->atp->client->get(
            endpoint: BskyGraph::GetStarterPack,
            params: compact('starterPack')
        );

        return StarterPackView::fromArray($response->json()['starterPack']);
    }

    /**
     * Get multiple starter packs
     *
     * @see https://docs.bsky.app/docs/api/app-bsky-graph-get-starter-packs
     */
    public function getStarterPacks(array $uris): GetStarterPacksResponse
    {
        $response = $this->atp->client->get(
            endpoint: BskyGraph::GetStarterPacks,
            params: compact('uris')
        );

        return GetStarterPacksResponse::fromArray($response->json());
    }

    /**
     * Get suggested follows based on an actor
     *
     * @see https://docs.bsky.app/docs/api/app-bsky-graph-get-suggested-follows-by-actor
     */
    public function getSuggestedFollowsByActor(string $actor): GetSuggestedFollowsByActorResponse
    {
        $response = $this->atp->client->get(
            endpoint: BskyGraph::GetSuggestedFollowsByActor,
            params: compact('actor')
        );

        return GetSuggestedFollowsByActorResponse::fromArray($response->json());
    }
}
