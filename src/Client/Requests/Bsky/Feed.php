<?php

namespace SocialDept\AtpClient\Client\Requests\Bsky;

use SocialDept\AtpClient\Client\Requests\Request;
use SocialDept\AtpClient\Http\Response;

class Feed extends Request
{
    /**
     * Get timeline feed
     */
    public function getTimeline(int $limit = 50, ?string $cursor = null): Response
    {
        return $this->atp->client->get('app.bsky.feed.getTimeline', compact('limit', 'cursor'));
    }

    /**
     * Get author feed
     */
    public function getAuthorFeed(string $actor, int $limit = 50, ?string $cursor = null): Response
    {
        return $this->atp->client->get('app.bsky.feed.getAuthorFeed', compact('actor', 'limit', 'cursor'));
    }

    /**
     * Get post thread
     */
    public function getPostThread(string $uri, int $depth = 6): Response
    {
        return $this->atp->client->get('app.bsky.feed.getPostThread', compact('uri', 'depth'));
    }

    /**
     * Search posts
     */
    public function searchPosts(string $q, int $limit = 25, ?string $cursor = null): Response
    {
        return $this->atp->client->get('app.bsky.feed.searchPosts', compact('q', 'limit', 'cursor'));
    }

    /**
     * Get likes for a post
     */
    public function getLikes(string $uri, int $limit = 50, ?string $cursor = null): Response
    {
        return $this->atp->client->get('app.bsky.feed.getLikes', compact('uri', 'limit', 'cursor'));
    }

    /**
     * Get reposts for a post
     */
    public function getRepostedBy(string $uri, int $limit = 50, ?string $cursor = null): Response
    {
        return $this->atp->client->get('app.bsky.feed.getRepostedBy', compact('uri', 'limit', 'cursor'));
    }
}
