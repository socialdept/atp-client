<?php

namespace SocialDept\AtpClient\Attributes;

use Attribute;

/**
 * Documents that a method is a public endpoint that does not require authentication.
 *
 * This attribute currently serves as documentation to indicate which AT Protocol
 * endpoints can be called without an authenticated session. It helps developers
 * understand which endpoints work with `Atp::public()` against public API endpoints
 * like `https://public.api.bsky.app`.
 *
 * While this attribute does not currently perform runtime enforcement, scope
 * validation will be implemented in a future release. Correctly attributing
 * endpoints now ensures forward compatibility when enforcement is enabled.
 *
 * Public endpoints typically include operations like:
 * - Reading public profiles and posts
 * - Searching actors and content
 * - Resolving handles to DIDs
 * - Accessing repository data (sync endpoints)
 * - Describing servers and feed generators
 *
 * @example Basic usage
 * ```php
 * #[PublicEndpoint]
 * public function getProfile(string $actor): ProfileViewDetailed
 * ```
 *
 * @see \SocialDept\AtpClient\Attributes\ScopedEndpoint For endpoints that require authentication
 */
#[Attribute(Attribute::TARGET_METHOD)]
class PublicEndpoint
{
    /**
     * @param  string  $description  Human-readable description of the endpoint
     */
    public function __construct(
        public readonly string $description = '',
    ) {}
}
