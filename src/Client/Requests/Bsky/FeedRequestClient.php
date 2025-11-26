<?php

namespace SocialDept\AtpClient\Client\Requests\Bsky;

use SocialDept\AtpClient\Attributes\RequiresScope;
use SocialDept\AtpClient\Client\Requests\Request;
use SocialDept\AtpClient\Enums\Scope;
use SocialDept\AtpClient\Http\Response;

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
     * @requires transition:generic (rpc:app.bsky.feed.getAuthorFeed)
     *
     * @see https://docs.bsky.app/docs/api/app-bsky-feed-get-author-feed
     */
    #[RequiresScope(Scope::TransitionGeneric, granular: 'rpc:app.bsky.feed.getAuthorFeed')]
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
     * @requires transition:generic (rpc:app.bsky.feed.getPostThread)
     *
     * @see https://docs.bsky.app/docs/api/app-bsky-feed-get-post-thread
     */
    #[RequiresScope(Scope::TransitionGeneric, granular: 'rpc:app.bsky.feed.getPostThread')]
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
     * @requires transition:generic (rpc:app.bsky.feed.searchPosts)
     *
     * @see https://docs.bsky.app/docs/api/app-bsky-feed-search-posts
     */
    #[RequiresScope(Scope::TransitionGeneric, granular: 'rpc:app.bsky.feed.searchPosts')]
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
     * @requires transition:generic (rpc:app.bsky.feed.getLikes)
     *
     * @see https://docs.bsky.app/docs/api/app-bsky-feed-get-likes
     */
    #[RequiresScope(Scope::TransitionGeneric, granular: 'rpc:app.bsky.feed.getLikes')]
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
     * @requires transition:generic (rpc:app.bsky.feed.getRepostedBy)
     *
     * @see https://docs.bsky.app/docs/api/app-bsky-feed-get-reposted-by
     */
    #[RequiresScope(Scope::TransitionGeneric, granular: 'rpc:app.bsky.feed.getRepostedBy')]
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
