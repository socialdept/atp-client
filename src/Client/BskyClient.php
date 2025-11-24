<?php

namespace SocialDept\AtpClient\Client;

use Illuminate\Http\Client\Factory;
use SocialDept\AtpClient\Auth\DPoPNonceManager;
use SocialDept\AtpClient\Http\HasHttp;
use SocialDept\AtpClient\Http\Response;
use SocialDept\AtpClient\Session\SessionManager;

class BskyClient
{
    use HasHttp;

    public function __construct(
        SessionManager $sessions,
        Factory $http,
        string $identifier,
    ) {
        $this->sessions = $sessions;
        $this->http = $http;
        $this->identifier = $identifier;
        $this->nonceManager = app(DPoPNonceManager::class);
    }

    /**
     * Get timeline feed
     */
    public function getTimeline(int $limit = 50, ?string $cursor = null): Response
    {
        return $this->get('app.bsky.feed.getTimeline', compact('limit', 'cursor'));
    }

    /**
     * Get author feed
     */
    public function getAuthorFeed(string $actor, int $limit = 50, ?string $cursor = null): Response
    {
        return $this->get('app.bsky.feed.getAuthorFeed', compact('actor', 'limit', 'cursor'));
    }

    /**
     * Get post thread
     */
    public function getPostThread(string $uri, int $depth = 6): Response
    {
        return $this->get('app.bsky.feed.getPostThread', compact('uri', 'depth'));
    }

    /**
     * Get actor profile
     */
    public function getProfile(string $actor): Response
    {
        return $this->get('app.bsky.actor.getProfile', compact('actor'));
    }

    /**
     * Search posts
     */
    public function searchPosts(string $q, int $limit = 25, ?string $cursor = null): Response
    {
        return $this->get('app.bsky.feed.searchPosts', compact('q', 'limit', 'cursor'));
    }

    /**
     * Get likes for a post
     */
    public function getLikes(string $uri, int $limit = 50, ?string $cursor = null): Response
    {
        return $this->get('app.bsky.feed.getLikes', compact('uri', 'limit', 'cursor'));
    }

    /**
     * Get reposts for a post
     */
    public function getRepostedBy(string $uri, int $limit = 50, ?string $cursor = null): Response
    {
        return $this->get('app.bsky.feed.getRepostedBy', compact('uri', 'limit', 'cursor'));
    }
}
