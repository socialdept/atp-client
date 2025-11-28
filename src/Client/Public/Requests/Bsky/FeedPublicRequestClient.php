<?php

namespace SocialDept\AtpClient\Client\Public\Requests\Bsky;

use SocialDept\AtpClient\Client\Public\Requests\PublicRequest;
use SocialDept\AtpClient\Enums\Nsid\BskyFeed;
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
use SocialDept\AtpClient\Data\Responses\Bsky\Feed\SearchPostsResponse;

class FeedPublicRequestClient extends PublicRequest
{
    public function describeFeedGenerator(): DescribeFeedGeneratorResponse
    {
        $response = $this->atp->client->get(
            endpoint: BskyFeed::DescribeFeedGenerator
        );

        return DescribeFeedGeneratorResponse::fromArray($response->json());
    }

    public function getAuthorFeed(string $actor, int $limit = 50, ?string $cursor = null, ?string $filter = null): GetAuthorFeedResponse
    {
        $response = $this->atp->client->get(
            endpoint: BskyFeed::GetAuthorFeed,
            params: compact('actor', 'limit', 'cursor', 'filter')
        );

        return GetAuthorFeedResponse::fromArray($response->json());
    }

    public function getActorFeeds(string $actor, int $limit = 50, ?string $cursor = null): GetActorFeedsResponse
    {
        $response = $this->atp->client->get(
            endpoint: BskyFeed::GetActorFeeds,
            params: compact('actor', 'limit', 'cursor')
        );

        return GetActorFeedsResponse::fromArray($response->json());
    }

    public function getActorLikes(string $actor, int $limit = 50, ?string $cursor = null): GetActorLikesResponse
    {
        $response = $this->atp->client->get(
            endpoint: BskyFeed::GetActorLikes,
            params: compact('actor', 'limit', 'cursor')
        );

        return GetActorLikesResponse::fromArray($response->json());
    }

    public function getFeed(string $feed, int $limit = 50, ?string $cursor = null): GetFeedResponse
    {
        $response = $this->atp->client->get(
            endpoint: BskyFeed::GetFeed,
            params: compact('feed', 'limit', 'cursor')
        );

        return GetFeedResponse::fromArray($response->json());
    }

    public function getFeedGenerator(string $feed): GetFeedGeneratorResponse
    {
        $response = $this->atp->client->get(
            endpoint: BskyFeed::GetFeedGenerator,
            params: compact('feed')
        );

        return GetFeedGeneratorResponse::fromArray($response->json());
    }

    public function getFeedGenerators(array $feeds): GetFeedGeneratorsResponse
    {
        $response = $this->atp->client->get(
            endpoint: BskyFeed::GetFeedGenerators,
            params: compact('feeds')
        );

        return GetFeedGeneratorsResponse::fromArray($response->json());
    }

    public function getLikes(string $uri, int $limit = 50, ?string $cursor = null, ?string $cid = null): GetLikesResponse
    {
        $response = $this->atp->client->get(
            endpoint: BskyFeed::GetLikes,
            params: compact('uri', 'limit', 'cursor', 'cid')
        );

        return GetLikesResponse::fromArray($response->json());
    }

    public function getPostThread(string $uri, int $depth = 6, int $parentHeight = 80): GetPostThreadResponse
    {
        $response = $this->atp->client->get(
            endpoint: BskyFeed::GetPostThread,
            params: compact('uri', 'depth', 'parentHeight')
        );

        return GetPostThreadResponse::fromArray($response->json());
    }

    public function getPosts(array $uris): GetPostsResponse
    {
        $response = $this->atp->client->get(
            endpoint: BskyFeed::GetPosts,
            params: compact('uris')
        );

        return GetPostsResponse::fromArray($response->json());
    }

    public function getQuotes(string $uri, int $limit = 50, ?string $cursor = null, ?string $cid = null): GetQuotesResponse
    {
        $response = $this->atp->client->get(
            endpoint: BskyFeed::GetQuotes,
            params: compact('uri', 'limit', 'cursor', 'cid')
        );

        return GetQuotesResponse::fromArray($response->json());
    }

    public function getRepostedBy(string $uri, int $limit = 50, ?string $cursor = null, ?string $cid = null): GetRepostedByResponse
    {
        $response = $this->atp->client->get(
            endpoint: BskyFeed::GetRepostedBy,
            params: compact('uri', 'limit', 'cursor', 'cid')
        );

        return GetRepostedByResponse::fromArray($response->json());
    }

    public function getSuggestedFeeds(int $limit = 50, ?string $cursor = null): GetSuggestedFeedsResponse
    {
        $response = $this->atp->client->get(
            endpoint: BskyFeed::GetSuggestedFeeds,
            params: compact('limit', 'cursor')
        );

        return GetSuggestedFeedsResponse::fromArray($response->json());
    }

    public function searchPosts(string $q, int $limit = 25, ?string $cursor = null, ?string $sort = null): SearchPostsResponse
    {
        $response = $this->atp->client->get(
            endpoint: BskyFeed::SearchPosts,
            params: compact('q', 'limit', 'cursor', 'sort')
        );

        return SearchPostsResponse::fromArray($response->json());
    }
}
