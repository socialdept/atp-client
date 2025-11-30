<?php

namespace SocialDept\AtpClient\Attributes;

use Attribute;
use SocialDept\AtpClient\Enums\Scope;

/**
 * Documents that a method requires authentication with specific OAuth scopes.
 *
 * This attribute currently serves as documentation to indicate which AT Protocol
 * endpoints require authentication and what scopes they need. It helps developers
 * understand scope requirements when building applications.
 *
 * While this attribute does not currently perform runtime enforcement, scope
 * validation will be implemented in a future release. Correctly attributing
 * endpoints now ensures forward compatibility when enforcement is enabled.
 *
 * The AT Protocol currently uses "transition scopes" (like `transition:generic`) while
 * moving toward more granular scopes. The `granular` parameter allows documenting the
 * future granular scope that will replace the transition scope.
 *
 * @example Basic usage with a transition scope
 * ```php
 * #[ScopedEndpoint(Scope::TransitionGeneric)]
 * public function getTimeline(): GetTimelineResponse
 * ```
 *
 * @example With future granular scope documented
 * ```php
 * #[ScopedEndpoint(Scope::TransitionGeneric, granular: 'rpc:app.bsky.feed.getTimeline')]
 * public function getTimeline(): GetTimelineResponse
 * ```
 *
 * @see \SocialDept\AtpClient\Attributes\PublicEndpoint For endpoints that don't require authentication
 * @see \SocialDept\AtpClient\Enums\Scope For available scope values
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class ScopedEndpoint
{
    public array $scopes;

    /**
     * @param  string|Scope|array<string|Scope>  $scopes  Required scope(s) for this method
     * @param  string|null  $granular  Future granular scope equivalent
     * @param  string  $description  Human-readable description of scope requirement
     */
    public function __construct(
        string|Scope|array $scopes,
        public readonly ?string $granular = null,
        public readonly string $description = '',
    ) {
        $this->scopes = $this->normalizeScopes($scopes);
    }

    protected function normalizeScopes(string|Scope|array $scopes): array
    {
        if (! is_array($scopes)) {
            $scopes = [$scopes];
        }

        return array_map(
            fn ($scope) => $scope instanceof Scope ? $scope->value : $scope,
            $scopes
        );
    }
}
