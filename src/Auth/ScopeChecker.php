<?php

namespace SocialDept\AtpClient\Auth;

use Illuminate\Support\Facades\Log;
use SocialDept\AtpClient\Enums\Scope;
use SocialDept\AtpClient\Enums\ScopeEnforcementLevel;
use SocialDept\AtpClient\Exceptions\MissingScopeException;
use SocialDept\AtpClient\Session\Session;

class ScopeChecker
{
    public function __construct(
        protected ScopeEnforcementLevel $enforcement = ScopeEnforcementLevel::Permissive
    ) {}

    /**
     * Check if the session has all required scopes.
     *
     * @param  array<string|Scope>  $requiredScopes
     */
    public function check(Session $session, array $requiredScopes): bool
    {
        $required = $this->normalizeScopes($requiredScopes);
        $granted = $session->scopes();

        foreach ($required as $scope) {
            if (! $this->sessionHasScope($session, $scope)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check scopes and handle enforcement based on configuration.
     *
     * @param  array<string|Scope>  $requiredScopes
     *
     * @throws MissingScopeException
     */
    public function checkOrFail(Session $session, array $requiredScopes): void
    {
        if ($this->check($session, $requiredScopes)) {
            return;
        }

        $required = $this->normalizeScopes($requiredScopes);
        $granted = $session->scopes();
        $missing = array_diff($required, $granted);

        if ($this->enforcement === ScopeEnforcementLevel::Strict) {
            throw new MissingScopeException($missing, $granted);
        }

        Log::warning('ATP Client: Missing required scope(s)', [
            'required' => $required,
            'granted' => $granted,
            'missing' => $missing,
            'did' => $session->did(),
        ]);
    }

    /**
     * Check if the session has a specific scope.
     */
    public function hasScope(Session $session, string|Scope $scope): bool
    {
        $scope = $scope instanceof Scope ? $scope->value : $scope;

        return $this->sessionHasScope($session, $scope);
    }

    /**
     * Check if the session matches a granular scope pattern.
     *
     * Supports patterns like:
     * - repo:app.bsky.feed.post?action=create
     * - repo:app.bsky.feed.*
     * - rpc:app.bsky.feed.*
     * - blob:image/*
     */
    public function matchesGranular(Session $session, string $pattern): bool
    {
        $granted = $session->scopes();

        // Check for exact match first
        if (in_array($pattern, $granted, true)) {
            return true;
        }

        // Handle repo: scopes with action semantics
        if (str_starts_with($pattern, 'repo:')) {
            foreach ($granted as $scope) {
                if (str_starts_with($scope, 'repo:') && $this->matchesRepoScope($pattern, $scope)) {
                    return true;
                }
            }
        }

        // Check for wildcard matches
        $patternRegex = $this->patternToRegex($pattern);

        foreach ($granted as $scope) {
            if (preg_match($patternRegex, $scope)) {
                return true;
            }
        }

        // Check if granted scope is a superset (wildcard in granted scope)
        foreach ($granted as $scope) {
            $grantedRegex = $this->patternToRegex($scope);
            if (preg_match($grantedRegex, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a required repo scope is satisfied by a granted repo scope.
     *
     * Per AT Protocol spec: "If not defined, all operations are allowed."
     * - repo:collection (no action) grants ALL actions
     * - repo:collection?action=create grants only create
     * - repo:* grants all collections with all actions
     */
    protected function matchesRepoScope(string $required, string $granted): bool
    {
        $requiredParsed = $this->parseRepoScope($required);
        $grantedParsed = $this->parseRepoScope($granted);

        // Check collection match (with wildcard support)
        if (! $this->collectionsMatch($requiredParsed['collection'], $grantedParsed['collection'])) {
            return false;
        }

        // If granted has no actions, it grants ALL actions
        if (empty($grantedParsed['actions'])) {
            return true;
        }

        // If required has no actions, we need all actions granted
        if (empty($requiredParsed['actions'])) {
            // Required needs all actions, but granted is restricted
            return false;
        }

        // Check if all required actions are in granted actions
        return empty(array_diff($requiredParsed['actions'], $grantedParsed['actions']));
    }

    /**
     * Parse a repo scope into collection and actions.
     *
     * Handles formats like:
     * - repo:app.bsky.feed.post
     * - repo:app.bsky.feed.post?action=create
     * - repo:app.bsky.feed.post?action=create&action=update&action=delete
     * - repo:*
     * - repo:*?action=delete
     *
     * @return array{collection: string, actions: array<string>}
     */
    protected function parseRepoScope(string $scope): array
    {
        $parts = explode('?', $scope, 2);
        $collection = substr($parts[0], 5); // Remove 'repo:'

        $actions = [];
        if (isset($parts[1])) {
            // Parse action=create&action=update&action=delete format
            // PHP's parse_str doesn't handle repeated params well
            preg_match_all('/action=([^&]+)/', $parts[1], $matches);
            if (! empty($matches[1])) {
                $actions = array_map('urldecode', $matches[1]);
            }
        }

        return ['collection' => $collection, 'actions' => $actions];
    }

    /**
     * Check if a required collection matches a granted collection.
     */
    protected function collectionsMatch(string $required, string $granted): bool
    {
        if ($granted === '*') {
            return true;
        }

        return $required === $granted;
    }

    /**
     * Check if the session has repo access for a specific collection and action.
     */
    public function checkRepoScope(Session $session, string $collection, string $action): bool
    {
        $required = "repo:{$collection}?action={$action}";

        return $this->sessionHasScope($session, $required);
    }

    /**
     * Check repo scope and handle enforcement based on configuration.
     *
     * @throws MissingScopeException
     */
    public function checkRepoScopeOrFail(Session $session, string $collection, string $action): void
    {
        $required = "repo:{$collection}?action={$action}";

        $this->checkOrFail($session, [$required]);
    }

    /**
     * Get the current enforcement level.
     */
    public function enforcement(): ScopeEnforcementLevel
    {
        return $this->enforcement;
    }

    /**
     * Create a new instance with a different enforcement level.
     */
    public function withEnforcement(ScopeEnforcementLevel $enforcement): self
    {
        return new self($enforcement);
    }

    /**
     * @param  array<string|Scope>  $scopes
     * @return array<string>
     */
    protected function normalizeScopes(array $scopes): array
    {
        return array_map(
            fn ($scope) => $scope instanceof Scope ? $scope->value : $scope,
            $scopes
        );
    }

    protected function sessionHasScope(Session $session, string $scope): bool
    {
        // Direct match
        if ($session->hasScope($scope)) {
            return true;
        }

        // Check granular pattern matching
        return $this->matchesGranular($session, $scope);
    }

    protected function patternToRegex(string $pattern): string
    {
        // Escape regex special characters except *
        $escaped = preg_quote($pattern, '/');

        // Replace \* with .* for wildcard matching
        $regex = str_replace('\*', '.*', $escaped);

        return '/^'.$regex.'$/';
    }
}
