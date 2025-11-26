<?php

namespace SocialDept\AtpClient\Client\Requests\Bsky;

use SocialDept\AtpClient\Client\Requests\Request;
use SocialDept\AtpClient\Http\Response;

class FeedRequestClient extends Request
{
    /**
     * Get timeline feed
     *
     * @see https://docs.bsky.app/docs/api/app-bsky-feed-get-timeline
     */
    public function getTimeline(int $limit = 50, ?string $cursor = null): Response
    {
        return $this->atp->client->get(
            endpoint: 'app.bsky.feed.getTimeline',
            params: compact('limit', 'cursor')
        );
    }

    /**
     * Get author feed
     *
     * @see https://docs.bsky.app/docs/api/app-bsky-feed-get-author-feed
     */
    public function getAuthorFeed(
        string $actor,
        int $limit = 50,
        ?string $cursor = null
    ): Response {
        return $this->atp->client->get(
            endpoint: 'app.bsky.feed.getAuthorFeed',
            params: compact('actor', 'limit', 'cursor')
        );
    }

    /**
     * Get post thread
     *
     * @see https://docs.bsky.app/docs/api/app-bsky-feed-get-post-thread
     */
    public function getPostThread(string $uri, int $depth = 6): Response
    {
        return $this->atp->client->get(
            endpoint: 'app.bsky.feed.getPostThread',
            params: compact('uri', 'depth')
        );
    }

    /**
     * Search posts
     *
     * @see https://docs.bsky.app/docs/api/app-bsky-feed-search-posts
     */
    public function searchPosts(
        string $q,
        int $limit = 25,
        ?string $cursor = null
    ): Response {
        return $this->atp->client->get(
            endpoint: 'app.bsky.feed.searchPosts',
            params: compact('q', 'limit', 'cursor')
        );
    }

    /**
     * Get likes for a post
     *
     * @see https://docs.bsky.app/docs/api/app-bsky-feed-get-likes
     */
    public function getLikes(
        string $uri,
        int $limit = 50,
        ?string $cursor = null
    ): Response {
        return $this->atp->client->get(
            endpoint: 'app.bsky.feed.getLikes',
            params: compact('uri', 'limit', 'cursor')
        );
    }

    /**
     * Get reposts for a post
     *
     * @see https://docs.bsky.app/docs/api/app-bsky-feed-get-reposted-by
     */
    public function getRepostedBy(
        string $uri,
        int $limit = 50,
        ?string $cursor = null
    ): Response {
        return $this->atp->client->get(
            endpoint: 'app.bsky.feed.getRepostedBy',
            params: compact('uri', 'limit', 'cursor')
        );
    }
}
