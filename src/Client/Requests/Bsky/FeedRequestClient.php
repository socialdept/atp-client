<?php

namespace SocialDept\AtpClient\Client\Requests\Bsky;

use SocialDept\AtpClient\Attributes\RequiresScope;
use SocialDept\AtpClient\Client\Requests\Request;
use SocialDept\AtpClient\Data\Responses\Bsky\Feed\GetAuthorFeedResponse;
use SocialDept\AtpClient\Data\Responses\Bsky\Feed\GetLikesResponse;
use SocialDept\AtpClient\Data\Responses\Bsky\Feed\GetPostThreadResponse;
use SocialDept\AtpClient\Data\Responses\Bsky\Feed\GetRepostedByResponse;
use SocialDept\AtpClient\Data\Responses\Bsky\Feed\GetTimelineResponse;
use SocialDept\AtpClient\Data\Responses\Bsky\Feed\SearchPostsResponse;
use SocialDept\AtpClient\Enums\Nsid\BskyFeed;
use SocialDept\AtpClient\Enums\Scope;

class FeedRequestClient extends Request
{
    /**
     * Get timeline feed
     *
     * @requires transition:generic (rpc:app.bsky.feed.getTimeline)
     *
     * @see https://docs.bsky.app/docs/api/app-bsky-feed-get-timeline
     */
    #[RequiresScope(Scope::TransitionGeneric, granular: 'rpc:app.bsky.feed.getTimeline')]
    public function getTimeline(int $limit = 50, ?string $cursor = null): GetTimelineResponse
    {
        $response = $this->atp->client->get(
            endpoint: BskyFeed::GetTimeline,
            params: compact('limit', 'cursor')
        );

        return GetTimelineResponse::fromArray($response->json());
    }

    /**
     * Get author feed
     *
     * @requires transition:generic (rpc:app.bsky.feed.getAuthorFeed)
     *
     * @see https://docs.bsky.app/docs/api/app-bsky-feed-get-author-feed
     */
    #[RequiresScope(Scope::TransitionGeneric, granular: 'rpc:app.bsky.feed.getAuthorFeed')]
    public function getAuthorFeed(
        string $actor,
        int $limit = 50,
        ?string $cursor = null
    ): GetAuthorFeedResponse {
        $response = $this->atp->client->get(
            endpoint: BskyFeed::GetAuthorFeed,
            params: compact('actor', 'limit', 'cursor')
        );

        return GetAuthorFeedResponse::fromArray($response->json());
    }

    /**
     * Get post thread
     *
     * @requires transition:generic (rpc:app.bsky.feed.getPostThread)
     *
     * @see https://docs.bsky.app/docs/api/app-bsky-feed-get-post-thread
     */
    #[RequiresScope(Scope::TransitionGeneric, granular: 'rpc:app.bsky.feed.getPostThread')]
    public function getPostThread(string $uri, int $depth = 6): GetPostThreadResponse
    {
        $response = $this->atp->client->get(
            endpoint: BskyFeed::GetPostThread,
            params: compact('uri', 'depth')
        );

        return GetPostThreadResponse::fromArray($response->json());
    }

    /**
     * Search posts
     *
     * @requires transition:generic (rpc:app.bsky.feed.searchPosts)
     *
     * @see https://docs.bsky.app/docs/api/app-bsky-feed-search-posts
     */
    #[RequiresScope(Scope::TransitionGeneric, granular: 'rpc:app.bsky.feed.searchPosts')]
    public function searchPosts(
        string $q,
        int $limit = 25,
        ?string $cursor = null
    ): SearchPostsResponse {
        $response = $this->atp->client->get(
            endpoint: BskyFeed::SearchPosts,
            params: compact('q', 'limit', 'cursor')
        );

        return SearchPostsResponse::fromArray($response->json());
    }

    /**
     * Get likes for a post
     *
     * @requires transition:generic (rpc:app.bsky.feed.getLikes)
     *
     * @see https://docs.bsky.app/docs/api/app-bsky-feed-get-likes
     */
    #[RequiresScope(Scope::TransitionGeneric, granular: 'rpc:app.bsky.feed.getLikes')]
    public function getLikes(
        string $uri,
        int $limit = 50,
        ?string $cursor = null
    ): GetLikesResponse {
        $response = $this->atp->client->get(
            endpoint: BskyFeed::GetLikes,
            params: compact('uri', 'limit', 'cursor')
        );

        return GetLikesResponse::fromArray($response->json());
    }

    /**
     * Get reposts for a post
     *
     * @requires transition:generic (rpc:app.bsky.feed.getRepostedBy)
     *
     * @see https://docs.bsky.app/docs/api/app-bsky-feed-get-reposted-by
     */
    #[RequiresScope(Scope::TransitionGeneric, granular: 'rpc:app.bsky.feed.getRepostedBy')]
    public function getRepostedBy(
        string $uri,
        int $limit = 50,
        ?string $cursor = null
    ): GetRepostedByResponse {
        $response = $this->atp->client->get(
            endpoint: BskyFeed::GetRepostedBy,
            params: compact('uri', 'limit', 'cursor')
        );

        return GetRepostedByResponse::fromArray($response->json());
    }
}
