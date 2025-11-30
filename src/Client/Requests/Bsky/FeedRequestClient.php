<?php

namespace SocialDept\AtpClient\Client\Requests\Bsky;

use SocialDept\AtpClient\Attributes\RequiresScope;
use SocialDept\AtpClient\Client\Requests\Request;
use SocialDept\AtpClient\Data\Responses\Bsky\Feed\DescribeFeedGeneratorResponse;
use SocialDept\AtpClient\Data\Responses\Bsky\Feed\GetActorFeedsResponse;
use SocialDept\AtpClient\Data\Responses\Bsky\Feed\GetActorLikesResponse;
use SocialDept\AtpClient\Data\Responses\Bsky\Feed\GetAuthorFeedResponse;
use SocialDept\AtpClient\Data\Responses\Bsky\Feed\GetFeedGeneratorResponse;
use SocialDept\AtpClient\Data\Responses\Bsky\Feed\GetFeedGeneratorsResponse;
use SocialDept\AtpClient\Data\Responses\Bsky\Feed\GetFeedResponse;
use SocialDept\AtpClient\Data\Responses\Bsky\Feed\GetLikesResponse;
use SocialDept\AtpClient\Data\Responses\Bsky\Feed\GetPostsResponse;
use SocialDept\AtpClient\Data\Responses\Bsky\Feed\GetPostThreadResponse;
use SocialDept\AtpClient\Data\Responses\Bsky\Feed\GetQuotesResponse;
use SocialDept\AtpClient\Data\Responses\Bsky\Feed\GetRepostedByResponse;
use SocialDept\AtpClient\Data\Responses\Bsky\Feed\GetSuggestedFeedsResponse;
use SocialDept\AtpClient\Data\Responses\Bsky\Feed\GetTimelineResponse;
use SocialDept\AtpClient\Data\Responses\Bsky\Feed\SearchPostsResponse;
use SocialDept\AtpClient\Enums\Nsid\BskyFeed;
use SocialDept\AtpClient\Enums\Scope;

class FeedRequestClient extends Request
{
    /**
     * Describe feed generator
     *
     * @see https://docs.bsky.app/docs/api/app-bsky-feed-describe-feed-generator
     */
    public function describeFeedGenerator(): DescribeFeedGeneratorResponse
    {
        $response = $this->atp->client->get(
            endpoint: BskyFeed::DescribeFeedGenerator
        );

        return DescribeFeedGeneratorResponse::fromArray($response->json());
    }

    /**
     * Get timeline feed (requires authentication)
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
     * @see https://docs.bsky.app/docs/api/app-bsky-feed-get-author-feed
     */
    public function getAuthorFeed(
        string $actor,
        int $limit = 50,
        ?string $cursor = null,
        ?string $filter = null
    ): GetAuthorFeedResponse {
        $response = $this->atp->client->get(
            endpoint: BskyFeed::GetAuthorFeed,
            params: compact('actor', 'limit', 'cursor', 'filter')
        );

        return GetAuthorFeedResponse::fromArray($response->json());
    }

    /**
     * Get feeds created by an actor
     *
     * @see https://docs.bsky.app/docs/api/app-bsky-feed-get-actor-feeds
     */
    public function getActorFeeds(string $actor, int $limit = 50, ?string $cursor = null): GetActorFeedsResponse
    {
        $response = $this->atp->client->get(
            endpoint: BskyFeed::GetActorFeeds,
            params: compact('actor', 'limit', 'cursor')
        );

        return GetActorFeedsResponse::fromArray($response->json());
    }

    /**
     * Get posts liked by an actor
     *
     * @see https://docs.bsky.app/docs/api/app-bsky-feed-get-actor-likes
     */
    public function getActorLikes(string $actor, int $limit = 50, ?string $cursor = null): GetActorLikesResponse
    {
        $response = $this->atp->client->get(
            endpoint: BskyFeed::GetActorLikes,
            params: compact('actor', 'limit', 'cursor')
        );

        return GetActorLikesResponse::fromArray($response->json());
    }

    /**
     * Get a feed
     *
     * @see https://docs.bsky.app/docs/api/app-bsky-feed-get-feed
     */
    public function getFeed(string $feed, int $limit = 50, ?string $cursor = null): GetFeedResponse
    {
        $response = $this->atp->client->get(
            endpoint: BskyFeed::GetFeed,
            params: compact('feed', 'limit', 'cursor')
        );

        return GetFeedResponse::fromArray($response->json());
    }

    /**
     * Get a feed generator
     *
     * @see https://docs.bsky.app/docs/api/app-bsky-feed-get-feed-generator
     */
    public function getFeedGenerator(string $feed): GetFeedGeneratorResponse
    {
        $response = $this->atp->client->get(
            endpoint: BskyFeed::GetFeedGenerator,
            params: compact('feed')
        );

        return GetFeedGeneratorResponse::fromArray($response->json());
    }

    /**
     * Get multiple feed generators
     *
     * @see https://docs.bsky.app/docs/api/app-bsky-feed-get-feed-generators
     */
    public function getFeedGenerators(array $feeds): GetFeedGeneratorsResponse
    {
        $response = $this->atp->client->get(
            endpoint: BskyFeed::GetFeedGenerators,
            params: compact('feeds')
        );

        return GetFeedGeneratorsResponse::fromArray($response->json());
    }

    /**
     * Get post thread
     *
     * @see https://docs.bsky.app/docs/api/app-bsky-feed-get-post-thread
     */
    public function getPostThread(string $uri, int $depth = 6, int $parentHeight = 80): GetPostThreadResponse
    {
        $response = $this->atp->client->get(
            endpoint: BskyFeed::GetPostThread,
            params: compact('uri', 'depth', 'parentHeight')
        );

        return GetPostThreadResponse::fromArray($response->json());
    }

    /**
     * Get multiple posts by URI
     *
     * @see https://docs.bsky.app/docs/api/app-bsky-feed-get-posts
     */
    public function getPosts(array $uris): GetPostsResponse
    {
        $response = $this->atp->client->get(
            endpoint: BskyFeed::GetPosts,
            params: compact('uris')
        );

        return GetPostsResponse::fromArray($response->json());
    }

    /**
     * Get likes for a post
     *
     * @see https://docs.bsky.app/docs/api/app-bsky-feed-get-likes
     */
    public function getLikes(
        string $uri,
        int $limit = 50,
        ?string $cursor = null,
        ?string $cid = null
    ): GetLikesResponse {
        $response = $this->atp->client->get(
            endpoint: BskyFeed::GetLikes,
            params: compact('uri', 'limit', 'cursor', 'cid')
        );

        return GetLikesResponse::fromArray($response->json());
    }

    /**
     * Get quotes of a post
     *
     * @see https://docs.bsky.app/docs/api/app-bsky-feed-get-quotes
     */
    public function getQuotes(
        string $uri,
        int $limit = 50,
        ?string $cursor = null,
        ?string $cid = null
    ): GetQuotesResponse {
        $response = $this->atp->client->get(
            endpoint: BskyFeed::GetQuotes,
            params: compact('uri', 'limit', 'cursor', 'cid')
        );

        return GetQuotesResponse::fromArray($response->json());
    }

    /**
     * Get reposts for a post
     *
     * @see https://docs.bsky.app/docs/api/app-bsky-feed-get-reposted-by
     */
    public function getRepostedBy(
        string $uri,
        int $limit = 50,
        ?string $cursor = null,
        ?string $cid = null
    ): GetRepostedByResponse {
        $response = $this->atp->client->get(
            endpoint: BskyFeed::GetRepostedBy,
            params: compact('uri', 'limit', 'cursor', 'cid')
        );

        return GetRepostedByResponse::fromArray($response->json());
    }

    /**
     * Get suggested feeds
     *
     * @see https://docs.bsky.app/docs/api/app-bsky-feed-get-suggested-feeds
     */
    public function getSuggestedFeeds(int $limit = 50, ?string $cursor = null): GetSuggestedFeedsResponse
    {
        $response = $this->atp->client->get(
            endpoint: BskyFeed::GetSuggestedFeeds,
            params: compact('limit', 'cursor')
        );

        return GetSuggestedFeedsResponse::fromArray($response->json());
    }

    /**
     * Search posts
     *
     * @see https://docs.bsky.app/docs/api/app-bsky-feed-search-posts
     */
    public function searchPosts(
        string $q,
        int $limit = 25,
        ?string $cursor = null,
        ?string $sort = null
    ): SearchPostsResponse {
        $response = $this->atp->client->get(
            endpoint: BskyFeed::SearchPosts,
            params: compact('q', 'limit', 'cursor', 'sort')
        );

        return SearchPostsResponse::fromArray($response->json());
    }
}
