<?php

namespace SocialDept\AtpClient\Testing;

use BackedEnum;
use PHPUnit\Framework\Assert;
use SocialDept\AtpClient\AtpClient;
use SocialDept\AtpClient\Auth\OAuthEngine;
use SocialDept\AtpClient\Contracts\CredentialProvider;
use SocialDept\AtpClient\Http\Response;

class FakeAtpManager
{
    /** @var array<string, Response|array|\Closure|ResponseSequence> */
    protected array $stubs = [];

    protected bool $preventStrayRequests = false;

    /** @var FakeAtpClient[] Clients created during this fake session */
    protected array $clients = [];

    protected FakeOAuthEngine $oauthEngine;

    /**
     * @param  array<string|BackedEnum, Response|array|\Closure|ResponseSequence>  $stubs  Keyed by NSID endpoint or BackedEnum
     */
    public function __construct(array $stubs = [])
    {
        $this->stubs = $this->normalizeStubs($stubs);
        $this->oauthEngine = new FakeOAuthEngine();
    }

    // ─── Client Factory Methods ──────────────────────────────────────

    /**
     * Create a public-mode fake client.
     */
    public function public(?string $service = null): FakeAtpClient
    {
        $client = new FakeAtpClient(
            stubs: $this->stubs,
            serviceUrl: $service ?? 'https://public.api.bsky.app',
        );

        $client->fakeClient()->preventStrayRequests($this->preventStrayRequests);
        $this->clients[] = $client;

        return $client;
    }

    /**
     * Create an authenticated fake client.
     *
     * The fake client doesn't need real credentials — it creates a synthetic
     * session that grants all scopes so your code runs without auth errors.
     */
    public function as(string $actor): FakeAtpClient
    {
        $client = new FakeAtpClient(
            stubs: $this->stubs,
            did: $actor,
            serviceUrl: 'https://fake.pds.bsky.social',
        );

        $client->fakeClient()->preventStrayRequests($this->preventStrayRequests);
        $this->clients[] = $client;

        return $client;
    }

    /**
     * Create a client via app password login (no real login happens).
     */
    public function login(string $actor, string $password): FakeAtpClient
    {
        return $this->as($actor);
    }

    /**
     * Get the fake OAuth engine.
     */
    public function oauth(): FakeOAuthEngine
    {
        return $this->oauthEngine;
    }

    /**
     * Set a default credential provider (no-op in fake mode).
     */
    public function setDefaultProvider(CredentialProvider $provider): void
    {
        // No-op in fake mode
    }

    // ─── Stub Configuration ──────────────────────────────────────────

    /**
     * Add a stub for an endpoint.
     */
    public function stub(string|BackedEnum $endpoint, Response|array|\Closure|ResponseSequence $response): static
    {
        $endpoint = self::resolveEndpoint($endpoint);
        $this->stubs[$endpoint] = $response;

        // Update all existing clients
        foreach ($this->clients as $client) {
            $client->fakeClient()->stub($endpoint, $response);
        }

        return $this;
    }

    /**
     * Create a response sequence for an endpoint.
     */
    public function sequence(string|BackedEnum $endpoint): ResponseSequence
    {
        $endpoint = self::resolveEndpoint($endpoint);
        $sequence = new ResponseSequence();
        $this->stubs[$endpoint] = $sequence;

        // Update all existing clients
        foreach ($this->clients as $client) {
            $client->client->stub($endpoint, $sequence);
        }

        return $sequence;
    }

    /**
     * Prevent unstubbed endpoints from silently succeeding.
     */
    public function preventStrayRequests(bool $prevent = true): static
    {
        $this->preventStrayRequests = $prevent;

        foreach ($this->clients as $client) {
            $client->fakeClient()->preventStrayRequests($prevent);
        }

        return $this;
    }

    // ─── Assertions ──────────────────────────────────────────────────

    /**
     * Assert that an endpoint was called at least once.
     */
    public function assertCalled(string|BackedEnum $endpoint, ?\Closure $callback = null): static
    {
        $endpoint = self::resolveEndpoint($endpoint);
        $recorded = $this->getRecordedFor($endpoint);

        Assert::assertNotEmpty(
            $recorded,
            "Expected endpoint [{$endpoint}] to be called, but it was not."
        );

        if ($callback) {
            $matched = array_filter($recorded, $callback);
            Assert::assertNotEmpty(
                $matched,
                "Endpoint [{$endpoint}] was called but no call matched the given callback."
            );
        }

        return $this;
    }

    /**
     * Assert that an endpoint was not called.
     */
    public function assertNotCalled(string|BackedEnum $endpoint): static
    {
        $endpoint = self::resolveEndpoint($endpoint);
        $recorded = $this->getRecordedFor($endpoint);

        Assert::assertEmpty(
            $recorded,
            "Expected endpoint [{$endpoint}] to not be called, but it was called " . count($recorded) . ' time(s).'
        );

        return $this;
    }

    /**
     * Assert an endpoint was called exactly N times.
     */
    public function assertCalledTimes(string|BackedEnum $endpoint, int $times): static
    {
        $endpoint = self::resolveEndpoint($endpoint);
        $recorded = $this->getRecordedFor($endpoint);

        Assert::assertCount(
            $times,
            $recorded,
            "Expected endpoint [{$endpoint}] to be called {$times} time(s), but was called " . count($recorded) . ' time(s).'
        );

        return $this;
    }

    /**
     * Assert an endpoint was called with specific params/body.
     */
    public function assertCalledWith(string|BackedEnum $endpoint, \Closure $callback): static
    {
        return $this->assertCalled($endpoint, $callback);
    }

    /**
     * Assert no endpoints were called at all.
     */
    public function assertNothingCalled(): static
    {
        $all = $this->allRecorded();

        Assert::assertEmpty(
            $all,
            'Expected no ATP requests, but ' . count($all) . ' request(s) were made: ' .
            implode(', ', array_map(fn (RecordedRequest $r) => $r->endpoint, $all))
        );

        return $this;
    }

    /**
     * Assert that an endpoint was called in public mode.
     */
    public function assertCalledPublicly(string|BackedEnum $endpoint): static
    {
        $endpoint = self::resolveEndpoint($endpoint);
        $recorded = $this->getRecordedFor($endpoint);
        $public = array_filter($recorded, fn (RecordedRequest $r) => $r->isPublicMode);

        Assert::assertNotEmpty(
            $public,
            "Expected endpoint [{$endpoint}] to be called in public mode, but it was not."
        );

        return $this;
    }

    /**
     * Assert that an endpoint was called in authenticated mode.
     */
    public function assertCalledAuthenticated(string|BackedEnum $endpoint, ?string $did = null): static
    {
        $endpoint = self::resolveEndpoint($endpoint);
        $recorded = $this->getRecordedFor($endpoint);
        $authenticated = array_filter($recorded, function (RecordedRequest $r) use ($did) {
            if ($r->isPublicMode) {
                return false;
            }

            if ($did !== null && $r->did !== $did) {
                return false;
            }

            return true;
        });

        Assert::assertNotEmpty(
            $authenticated,
            "Expected endpoint [{$endpoint}] to be called in authenticated mode" .
            ($did ? " as [{$did}]" : '') . ', but it was not.'
        );

        return $this;
    }

    // ─── Recorded Request Access ─────────────────────────────────────

    /**
     * Get all recorded requests across all clients.
     *
     * @return RecordedRequest[]
     */
    public function allRecorded(): array
    {
        $all = [];

        foreach ($this->clients as $client) {
            $all = array_merge($all, $client->fakeClient()->recorded());
        }

        return $all;
    }

    /**
     * Get recorded requests matching an endpoint (supports wildcards).
     *
     * @return RecordedRequest[]
     */
    public function getRecordedFor(string|BackedEnum $endpoint): array
    {
        $endpoint = self::resolveEndpoint($endpoint);
        $all = $this->allRecorded();

        return array_values(array_filter($all, function (RecordedRequest $r) use ($endpoint) {
            if ($r->endpoint === $endpoint) {
                return true;
            }

            // Support wildcard pattern in assertion
            if (str_contains($endpoint, '*')) {
                return $r->matches($endpoint);
            }

            return false;
        }));
    }

    // ─── Internal ────────────────────────────────────────────────────

    /**
     * Resolve a string or BackedEnum to a string endpoint.
     */
    public static function resolveEndpoint(string|BackedEnum $endpoint): string
    {
        return $endpoint instanceof BackedEnum ? $endpoint->value : $endpoint;
    }

    /**
     * Normalize stubs — resolve BackedEnum keys to string keys.
     */
    protected function normalizeStubs(array $stubs): array
    {
        $normalized = [];

        foreach ($stubs as $endpoint => $stub) {
            $key = $endpoint instanceof BackedEnum ? $endpoint->value : $endpoint;
            $normalized[$key] = $stub;
        }

        return $normalized;
    }
}
