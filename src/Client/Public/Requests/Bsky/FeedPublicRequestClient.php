<?php

namespace SocialDept\AtpClient\Client\Public\Requests\Bsky;

use SocialDept\AtpClient\Client\Public\Requests\PublicRequest;
use SocialDept\AtpClient\Http\Response;

class FeedPublicRequestClient extends PublicRequest
{
    public function describeFeedGenerator(): Response
    {
        return $this->atp->client->get('app.bsky.feed.describeFeedGenerator');
    }

    public function getAuthorFeed(string $actor, int $limit = 50, ?string $cursor = null, ?string $filter = null): Response
    {
        return $this->atp->client->get('app.bsky.feed.getAuthorFeed', compact('actor', 'limit', 'cursor', 'filter'));
    }

    public function getActorFeeds(string $actor, int $limit = 50, ?string $cursor = null): Response
    {
        return $this->atp->client->get('app.bsky.feed.getActorFeeds', compact('actor', 'limit', 'cursor'));
    }

    public function getActorLikes(string $actor, int $limit = 50, ?string $cursor = null): Response
    {
        return $this->atp->client->get('app.bsky.feed.getActorLikes', compact('actor', 'limit', 'cursor'));
    }

    public function getFeed(string $feed, int $limit = 50, ?string $cursor = null): Response
    {
        return $this->atp->client->get('app.bsky.feed.getFeed', compact('feed', 'limit', 'cursor'));
    }

    public function getFeedGenerator(string $feed): Response
    {
        return $this->atp->client->get('app.bsky.feed.getFeedGenerator', compact('feed'));
    }

    public function getFeedGenerators(array $feeds): Response
    {
        return $this->atp->client->get('app.bsky.feed.getFeedGenerators', compact('feeds'));
    }

    public function getLikes(string $uri, int $limit = 50, ?string $cursor = null, ?string $cid = null): Response
    {
        return $this->atp->client->get('app.bsky.feed.getLikes', compact('uri', 'limit', 'cursor', 'cid'));
    }

    public function getPostThread(string $uri, int $depth = 6, int $parentHeight = 80): Response
    {
        return $this->atp->client->get('app.bsky.feed.getPostThread', compact('uri', 'depth', 'parentHeight'));
    }

    public function getPosts(array $uris): Response
    {
        return $this->atp->client->get('app.bsky.feed.getPosts', compact('uris'));
    }

    public function getQuotes(string $uri, int $limit = 50, ?string $cursor = null, ?string $cid = null): Response
    {
        return $this->atp->client->get('app.bsky.feed.getQuotes', compact('uri', 'limit', 'cursor', 'cid'));
    }

    public function getRepostedBy(string $uri, int $limit = 50, ?string $cursor = null, ?string $cid = null): Response
    {
        return $this->atp->client->get('app.bsky.feed.getRepostedBy', compact('uri', 'limit', 'cursor', 'cid'));
    }

    public function getSuggestedFeeds(int $limit = 50, ?string $cursor = null): Response
    {
        return $this->atp->client->get('app.bsky.feed.getSuggestedFeeds', compact('limit', 'cursor'));
    }

    public function searchPosts(string $q, int $limit = 25, ?string $cursor = null, ?string $sort = null): Response
    {
        return $this->atp->client->get('app.bsky.feed.searchPosts', compact('q', 'limit', 'cursor', 'sort'));
    }
}
