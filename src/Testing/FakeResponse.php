<?php

namespace SocialDept\AtpClient\Testing;

use GuzzleHttp\Psr7\Response as Psr7Response;
use Illuminate\Http\Client\Response as LaravelResponse;
use Illuminate\Support\Str;
use SocialDept\AtpClient\Http\Response;

class FakeResponse
{
    /**
     * Create a successful Response from an array.
     */
    public static function ok(array $data = [], int $status = 200): Response
    {
        return self::make($data, $status);
    }

    /**
     * Create an error Response matching AT Protocol XRPC error format.
     *
     * @param  string  $errorCode  AT Protocol error code (e.g., 'InvalidToken', 'AccountTakedown')
     * @param  string  $message  Human-readable error message
     * @param  int  $status  HTTP status code
     */
    public static function error(string $errorCode, string $message, int $status = 400): Response
    {
        return self::make([
            'error' => $errorCode,
            'message' => $message,
        ], $status);
    }

    // ─── AT Protocol Standard Errors ─────────────────────────────────

    /**
     * Token has expired — com.atproto.server.refreshSession error.
     */
    public static function expiredToken(string $message = 'Token has expired'): Response
    {
        return self::error('ExpiredToken', $message, 401);
    }

    /**
     * Token is invalid — com.atproto.server.refreshSession error.
     */
    public static function invalidToken(string $message = 'Token is invalid'): Response
    {
        return self::error('InvalidToken', $message, 401);
    }

    /**
     * Account has been taken down — com.atproto.server.createSession / refreshSession error.
     */
    public static function accountTakedown(string $message = 'Account has been taken down'): Response
    {
        return self::error('AccountTakedown', $message, 401);
    }

    /**
     * Account has been deactivated — com.atproto.server.createSession error.
     */
    public static function accountDeactivated(string $message = 'Account has been deactivated'): Response
    {
        return self::error('AccountDeactivated', $message, 401);
    }

    /**
     * Auth factor token required (2FA) — com.atproto.server.createSession error.
     */
    public static function authFactorTokenRequired(string $message = 'Authentication factor token required'): Response
    {
        return self::error('AuthFactorTokenRequired', $message, 401);
    }

    /**
     * Invalid swap — com.atproto.repo.createRecord / putRecord / deleteRecord error.
     * Occurs when a compare-and-swap check fails.
     */
    public static function invalidSwap(string $message = 'Record swap failed'): Response
    {
        return self::error('InvalidSwap', $message, 400);
    }

    /**
     * Rate limited response with Retry-After header.
     */
    public static function rateLimited(int $retryAfter = 30, string $message = 'Rate limit exceeded'): Response
    {
        return self::make(
            ['error' => 'RateLimitExceeded', 'message' => $message],
            429,
            ['Retry-After' => (string) $retryAfter]
        );
    }

    /**
     * Authentication required.
     */
    public static function authRequired(string $message = 'Authentication required'): Response
    {
        return self::error('AuthenticationRequired', $message, 401);
    }

    /**
     * Invalid request (malformed input) — generic 400 error.
     */
    public static function invalidRequest(string $message = 'Invalid request'): Response
    {
        return self::error('InvalidRequest', $message, 400);
    }

    /**
     * Method not implemented — generic 501 error.
     */
    public static function methodNotImplemented(string $message = 'Method not implemented'): Response
    {
        return self::error('MethodNotImplemented', $message, 501);
    }

    /**
     * Internal server error — generic 500 error.
     */
    public static function internalError(string $message = 'Internal server error'): Response
    {
        return self::error('InternalServerError', $message, 500);
    }

    /**
     * Upstream failure — when a dependent service is down.
     */
    public static function upstreamFailure(string $message = 'Upstream service unavailable'): Response
    {
        return self::error('UpstreamFailure', $message, 502);
    }

    // ─── Blob-specific Errors ────────────────────────────────────────

    /**
     * Blob too large — com.atproto.repo.uploadBlob error.
     */
    public static function blobTooLarge(string $message = 'Blob is too large'): Response
    {
        return self::error('BlobTooLarge', $message, 400);
    }

    // ─── Actor Responses ─────────────────────────────────────────────

    /**
     * Generate a profile response (app.bsky.actor.getProfile).
     *
     * @see https://docs.bsky.app/docs/api/app-bsky-actor-get-profile
     */
    public static function profile(array $overrides = []): array
    {
        return array_merge([
            'did' => 'did:plc:' . Str::random(24),
            'handle' => 'test.bsky.social',
            'displayName' => 'Test User',
            'description' => 'A test user profile',
            'avatar' => 'https://cdn.bsky.app/img/avatar/plain/did:plc:test/test@jpeg',
            'banner' => null,
            'followsCount' => 42,
            'followersCount' => 100,
            'postsCount' => 50,
            'indexedAt' => now()->toIso8601String(),
            'createdAt' => now()->subYear()->toIso8601String(),
            'labels' => [],
        ], $overrides);
    }

    /**
     * Generate multiple profiles response (app.bsky.actor.getProfiles).
     *
     * @see https://docs.bsky.app/docs/api/app-bsky-actor-get-profiles
     */
    public static function profiles(int $count = 3, array $overrides = []): array
    {
        return [
            'profiles' => array_map(
                fn (int $i) => self::profile(array_merge([
                    'handle' => "user{$i}.bsky.social",
                    'displayName' => "User {$i}",
                ], $overrides)),
                range(1, $count)
            ),
        ];
    }

    /**
     * Generate a typeahead search result (app.bsky.actor.searchActorsTypeahead).
     *
     * @see https://docs.bsky.app/docs/api/app-bsky-actor-search-actors-typeahead
     */
    public static function searchActorsTypeahead(int $count = 3, array $overrides = []): array
    {
        return [
            'actors' => array_map(
                fn (int $i) => array_merge([
                    'did' => 'did:plc:' . Str::random(24),
                    'handle' => "result{$i}.bsky.social",
                    'displayName' => "Result {$i}",
                    'avatar' => null,
                    'labels' => [],
                ], $overrides),
                range(1, $count)
            ),
        ];
    }

    /**
     * Generate a search actors result (app.bsky.actor.searchActors).
     *
     * @see https://docs.bsky.app/docs/api/app-bsky-actor-search-actors
     */
    public static function searchActors(int $count = 5, ?string $cursor = null, array $overrides = []): array
    {
        return array_filter([
            'actors' => array_map(
                fn (int $i) => self::profile(array_merge([
                    'handle' => "search{$i}.bsky.social",
                    'displayName' => "Search Result {$i}",
                ], $overrides)),
                range(1, $count)
            ),
            'cursor' => $cursor,
        ]);
    }

    /**
     * Generate a getSuggestions response (app.bsky.actor.getSuggestions).
     *
     * @see https://docs.bsky.app/docs/api/app-bsky-actor-get-suggestions
     */
    public static function getSuggestions(int $count = 5, ?string $cursor = null): array
    {
        return array_filter([
            'actors' => array_map(
                fn (int $i) => self::profile([
                    'handle' => "suggested{$i}.bsky.social",
                    'displayName' => "Suggested User {$i}",
                ]),
                range(1, $count)
            ),
            'cursor' => $cursor,
        ]);
    }

    // ─── Feed Responses ──────────────────────────────────────────────

    /**
     * Generate a post view (app.bsky.feed.defs#postView).
     */
    public static function post(array $overrides = []): array
    {
        $did = $overrides['author']['did'] ?? 'did:plc:' . Str::random(24);

        return array_merge([
            'uri' => "at://{$did}/app.bsky.feed.post/" . Str::random(13),
            'cid' => 'bafyrei' . Str::random(46),
            'author' => array_merge([
                'did' => $did,
                'handle' => 'poster.bsky.social',
                'displayName' => 'Post Author',
                'labels' => [],
            ], $overrides['author'] ?? []),
            'record' => array_merge([
                '$type' => 'app.bsky.feed.post',
                'text' => 'Hello, world!',
                'createdAt' => now()->toIso8601String(),
                'langs' => ['en'],
            ], $overrides['record'] ?? []),
            'replyCount' => 0,
            'repostCount' => 0,
            'likeCount' => 0,
            'quoteCount' => 0,
            'indexedAt' => now()->toIso8601String(),
            'labels' => [],
        ], $overrides);
    }

    /**
     * Generate a feed view post (app.bsky.feed.defs#feedViewPost).
     */
    public static function feedViewPost(array $overrides = []): array
    {
        return [
            'post' => self::post($overrides['post'] ?? $overrides),
            'reply' => $overrides['reply'] ?? null,
            'reason' => $overrides['reason'] ?? null,
        ];
    }

    /**
     * Generate a timeline response (app.bsky.feed.getTimeline).
     *
     * @see https://docs.bsky.app/docs/api/app-bsky-feed-get-timeline
     */
    public static function timeline(int $count = 5, ?string $cursor = null, array $overrides = []): array
    {
        return array_filter([
            'feed' => array_map(
                fn (int $i) => self::feedViewPost(array_merge([
                    'post' => ['record' => ['text' => "Post {$i}"]],
                ], $overrides)),
                range(1, $count)
            ),
            'cursor' => $cursor,
        ]);
    }

    /**
     * Generate a getPostThread response (app.bsky.feed.getPostThread).
     *
     * @see https://docs.bsky.app/docs/api/app-bsky-feed-get-post-thread
     */
    public static function getPostThread(array $overrides = []): array
    {
        return [
            'thread' => array_merge([
                '$type' => 'app.bsky.feed.defs#threadViewPost',
                'post' => self::post($overrides['post'] ?? []),
                'replies' => $overrides['replies'] ?? [],
            ], $overrides),
        ];
    }

    /**
     * Generate a getPosts response (app.bsky.feed.getPosts).
     *
     * @see https://docs.bsky.app/docs/api/app-bsky-feed-get-posts
     */
    public static function getPosts(int $count = 3): array
    {
        return [
            'posts' => array_map(
                fn (int $i) => self::post(['record' => ['text' => "Post {$i}"]]),
                range(1, $count)
            ),
        ];
    }

    // ─── Repo Responses ──────────────────────────────────────────────

    /**
     * Generate a createRecord response (com.atproto.repo.createRecord).
     *
     * @see https://docs.bsky.app/docs/api/com-atproto-repo-create-record
     */
    public static function createRecord(array $overrides = []): array
    {
        $did = $overrides['did'] ?? 'did:plc:' . Str::random(24);
        unset($overrides['did']);

        return array_merge([
            'uri' => "at://{$did}/app.bsky.feed.post/" . Str::random(13),
            'cid' => 'bafyrei' . Str::random(46),
            'commit' => [
                'cid' => 'bafyrei' . Str::random(46),
                'rev' => Str::random(13),
            ],
            'validationStatus' => 'valid',
        ], $overrides);
    }

    /**
     * Generate a putRecord response (com.atproto.repo.putRecord).
     *
     * @see https://docs.bsky.app/docs/api/com-atproto-repo-put-record
     */
    public static function putRecord(array $overrides = []): array
    {
        return self::createRecord($overrides);
    }

    /**
     * Generate a deleteRecord response (com.atproto.repo.deleteRecord).
     *
     * @see https://docs.bsky.app/docs/api/com-atproto-repo-delete-record
     */
    public static function deleteRecord(): array
    {
        return [
            'commit' => [
                'cid' => 'bafyrei' . Str::random(46),
                'rev' => Str::random(13),
            ],
        ];
    }

    /**
     * Generate a getRecord response (com.atproto.repo.getRecord).
     *
     * @see https://docs.bsky.app/docs/api/com-atproto-repo-get-record
     */
    public static function getRecord(array $overrides = []): array
    {
        $did = $overrides['did'] ?? 'did:plc:' . Str::random(24);
        unset($overrides['did']);

        return array_merge([
            'uri' => "at://{$did}/app.bsky.feed.post/" . Str::random(13),
            'cid' => 'bafyrei' . Str::random(46),
            'value' => $overrides['value'] ?? [
                '$type' => 'app.bsky.feed.post',
                'text' => 'Hello, world!',
                'createdAt' => now()->toIso8601String(),
            ],
        ], $overrides);
    }

    /**
     * Generate a listRecords response (com.atproto.repo.listRecords).
     *
     * @see https://docs.bsky.app/docs/api/com-atproto-repo-list-records
     */
    public static function listRecords(int $count = 5, ?string $cursor = null): array
    {
        $did = 'did:plc:' . Str::random(24);

        return array_filter([
            'records' => array_map(
                fn (int $i) => [
                    'uri' => "at://{$did}/app.bsky.feed.post/" . Str::random(13),
                    'cid' => 'bafyrei' . Str::random(46),
                    'value' => [
                        '$type' => 'app.bsky.feed.post',
                        'text' => "Record {$i}",
                        'createdAt' => now()->subMinutes($i)->toIso8601String(),
                    ],
                ],
                range(1, $count)
            ),
            'cursor' => $cursor,
        ]);
    }

    // ─── Blob Responses ──────────────────────────────────────────────

    /**
     * Generate an uploadBlob response (com.atproto.repo.uploadBlob).
     *
     * @see https://docs.bsky.app/docs/api/com-atproto-repo-upload-blob
     */
    public static function uploadBlob(string $mimeType = 'image/jpeg', int $size = 50000): array
    {
        return [
            'blob' => [
                '$type' => 'blob',
                'ref' => ['$link' => 'bafyrei' . Str::random(46)],
                'mimeType' => $mimeType,
                'size' => $size,
            ],
        ];
    }

    // ─── Session Responses ───────────────────────────────────────────

    /**
     * Generate a createSession response (com.atproto.server.createSession).
     *
     * @see https://docs.bsky.app/docs/api/com-atproto-server-create-session
     */
    public static function createSession(array $overrides = []): array
    {
        return array_merge([
            'did' => 'did:plc:' . Str::random(24),
            'handle' => 'test.bsky.social',
            'email' => 'test@example.com',
            'emailConfirmed' => true,
            'emailAuthFactor' => false,
            'accessJwt' => 'fake-access-' . Str::random(32),
            'refreshJwt' => 'fake-refresh-' . Str::random(32),
            'active' => true,
        ], $overrides);
    }

    /**
     * Generate a refreshSession response (com.atproto.server.refreshSession).
     *
     * @see https://docs.bsky.app/docs/api/com-atproto-server-refresh-session
     */
    public static function refreshSession(array $overrides = []): array
    {
        return array_merge([
            'did' => 'did:plc:' . Str::random(24),
            'handle' => 'test.bsky.social',
            'accessJwt' => 'fake-access-' . Str::random(32),
            'refreshJwt' => 'fake-refresh-' . Str::random(32),
            'active' => true,
        ], $overrides);
    }

    /**
     * Generate a getSession response (com.atproto.server.getSession).
     *
     * @see https://docs.bsky.app/docs/api/com-atproto-server-get-session
     */
    public static function getSession(array $overrides = []): array
    {
        return array_merge([
            'did' => 'did:plc:' . Str::random(24),
            'handle' => 'test.bsky.social',
            'email' => 'test@example.com',
            'emailConfirmed' => true,
            'emailAuthFactor' => false,
            'didDoc' => null,
            'active' => true,
            'status' => null,
        ], $overrides);
    }

    // ─── Graph Responses ─────────────────────────────────────────────

    /**
     * Generate a getFollowers response (app.bsky.graph.getFollowers).
     *
     * @see https://docs.bsky.app/docs/api/app-bsky-graph-get-followers
     */
    public static function followers(int $count = 5, ?string $cursor = null): array
    {
        return array_filter([
            'subject' => self::profile(),
            'followers' => array_map(
                fn (int $i) => self::profile([
                    'handle' => "follower{$i}.bsky.social",
                    'displayName' => "Follower {$i}",
                ]),
                range(1, $count)
            ),
            'cursor' => $cursor,
        ]);
    }

    /**
     * Generate a getFollows response (app.bsky.graph.getFollows).
     *
     * @see https://docs.bsky.app/docs/api/app-bsky-graph-get-follows
     */
    public static function follows(int $count = 5, ?string $cursor = null): array
    {
        return array_filter([
            'subject' => self::profile(),
            'follows' => array_map(
                fn (int $i) => self::profile([
                    'handle' => "following{$i}.bsky.social",
                    'displayName' => "Following {$i}",
                ]),
                range(1, $count)
            ),
            'cursor' => $cursor,
        ]);
    }

    // ─── Notification Responses ──────────────────────────────────────

    /**
     * Generate a listNotifications response (app.bsky.notification.listNotifications).
     *
     * @see https://docs.bsky.app/docs/api/app-bsky-notification-list-notifications
     */
    public static function notifications(int $count = 5, ?string $cursor = null): array
    {
        $reasons = ['like', 'repost', 'follow', 'mention', 'reply', 'quote'];

        return array_filter([
            'notifications' => array_map(
                fn (int $i) => [
                    'uri' => 'at://did:plc:' . Str::random(24) . '/app.bsky.feed.like/' . Str::random(13),
                    'cid' => 'bafyrei' . Str::random(46),
                    'author' => self::profile(['handle' => "notifier{$i}.bsky.social"]),
                    'reason' => $reasons[$i % count($reasons)],
                    'record' => [
                        '$type' => 'app.bsky.feed.like',
                        'subject' => self::strongRef(),
                        'createdAt' => now()->subMinutes($i)->toIso8601String(),
                    ],
                    'isRead' => false,
                    'indexedAt' => now()->subMinutes($i)->toIso8601String(),
                    'labels' => [],
                ],
                range(1, $count)
            ),
            'cursor' => $cursor,
        ]);
    }

    // ─── Composable Shape Builders ───────────────────────────────────

    /**
     * Generate a paginated list of feed view posts.
     *
     * Covers: getTimeline, getAuthorFeed, getFeed, getActorLikes, etc.
     */
    public static function feedList(int $count = 5, ?string $cursor = null, string $key = 'feed'): array
    {
        return array_filter([
            $key => array_map(
                fn (int $i) => self::feedViewPost([
                    'post' => ['record' => ['text' => "Post {$i}"]],
                ]),
                range(1, $count)
            ),
            'cursor' => $cursor,
        ]);
    }

    /**
     * Generate a paginated list of profile views.
     *
     * Covers: getFollowers, getFollows, searchActors, getSuggestions, etc.
     */
    public static function profileList(int $count = 5, ?string $cursor = null, string $key = 'actors'): array
    {
        return array_filter([
            $key => array_map(
                fn (int $i) => self::profile([
                    'handle' => "user{$i}.bsky.social",
                    'displayName' => "User {$i}",
                ]),
                range(1, $count)
            ),
            'cursor' => $cursor,
        ]);
    }

    /**
     * Generate a paginated list of post views.
     *
     * Covers: getPosts, getQuotes, searchPosts, etc.
     */
    public static function postList(int $count = 5, ?string $cursor = null, string $key = 'posts'): array
    {
        return array_filter([
            $key => array_map(
                fn (int $i) => self::post(['record' => ['text' => "Post {$i}"]]),
                range(1, $count)
            ),
            'cursor' => $cursor,
        ]);
    }

    /**
     * Generate a generic paginated list response.
     *
     * Escape hatch for endpoints not covered by specific factories.
     *
     * @param  array  $items  The list items
     * @param  string  $key  The response key for the list
     */
    public static function cursorList(array $items, ?string $cursor = null, string $key = 'items'): array
    {
        return array_filter([
            $key => $items,
            'cursor' => $cursor,
        ]);
    }

    // ─── Generic Helpers ─────────────────────────────────────────────

    /**
     * Generate a generic empty success response.
     */
    public static function empty(): array
    {
        return [];
    }

    /**
     * Generate a strong reference (com.atproto.repo.strongRef).
     */
    public static function strongRef(array $overrides = []): array
    {
        return array_merge([
            'uri' => 'at://did:plc:' . Str::random(24) . '/app.bsky.feed.post/' . Str::random(13),
            'cid' => 'bafyrei' . Str::random(46),
        ], $overrides);
    }

    // ─── Internal ────────────────────────────────────────────────────

    /**
     * Build a Response from raw data.
     *
     * @param  array  $data  Response body
     * @param  int  $status  HTTP status code
     * @param  array  $headers  Additional response headers
     */
    public static function make(array $data, int $status = 200, array $headers = []): Response
    {
        $body = json_encode($data);

        $psr7 = new Psr7Response(
            status: $status,
            headers: array_merge(['Content-Type' => 'application/json'], $headers),
            body: $body,
        );

        return new Response(new LaravelResponse($psr7));
    }
}
