<?php

namespace SocialDept\AtpClient\Testing;

use BackedEnum;
use SocialDept\AtpClient\AtpClient;
use SocialDept\AtpClient\Client\Client;
use SocialDept\AtpClient\Exceptions\AtpResponseException;
use SocialDept\AtpClient\Http\Response;

class FakeClient extends Client
{
    /** @var array<string, Response|array|\Closure|ResponseSequence> */
    protected array $stubs = [];

    /** @var RecordedRequest[] */
    protected array $recorded = [];

    protected bool $preventStrayRequests = false;

    protected ?string $fakeDid = null;

    public function __construct(
        AtpClient $parent,
        array $stubs = [],
        ?string $serviceUrl = null,
    ) {
        // Skip parent constructor — we don't need sessions or DPoP
        $this->atp = $parent;
        $this->serviceUrl = $serviceUrl ?? 'https://fake.bsky.social';
        $this->stubs = $stubs;
    }

    /**
     * Set a fake DID for authenticated mode simulation.
     */
    public function setFakeDid(?string $did): void
    {
        $this->fakeDid = $did;
    }

    /**
     * Set whether unstubbed endpoints should throw.
     */
    public function preventStrayRequests(bool $prevent = true): static
    {
        $this->preventStrayRequests = $prevent;

        return $this;
    }

    /**
     * Add a stub for an endpoint.
     */
    public function stub(string|BackedEnum $endpoint, Response|array|\Closure|ResponseSequence $response): static
    {
        $endpoint = FakeAtpManager::resolveEndpoint($endpoint);
        $this->stubs[$endpoint] = $response;

        return $this;
    }

    /**
     * Get a response sequence for an endpoint, creating one if it doesn't exist.
     */
    public function sequence(string $endpoint): ResponseSequence
    {
        if (! isset($this->stubs[$endpoint]) || ! $this->stubs[$endpoint] instanceof ResponseSequence) {
            $this->stubs[$endpoint] = new ResponseSequence();
        }

        return $this->stubs[$endpoint];
    }

    /**
     * Get all recorded requests.
     *
     * @return RecordedRequest[]
     */
    public function recorded(): array
    {
        return $this->recorded;
    }

    /**
     * Override the call method to intercept all XRPC requests.
     */
    protected function call(
        string|BackedEnum $endpoint,
        string $method,
        ?array $params = null,
        ?array $body = null
    ): Response {
        $endpoint = $endpoint instanceof BackedEnum ? $endpoint->value : $endpoint;

        $this->recorded[] = new RecordedRequest(
            endpoint: $endpoint,
            method: $method,
            params: $params,
            body: $body,
            did: $this->fakeDid,
            isPublicMode: $this->isPublicMode(),
        );

        return $this->resolveStub($endpoint, $method, $params, $body);
    }

    /**
     * Override postBlob to intercept blob uploads.
     */
    public function postBlob(string|BackedEnum $endpoint, string $data, string $mimeType): Response
    {
        $endpoint = $endpoint instanceof BackedEnum ? $endpoint->value : $endpoint;

        $this->recorded[] = new RecordedRequest(
            endpoint: $endpoint,
            method: 'POST_BLOB',
            params: ['mimeType' => $mimeType, 'size' => strlen($data)],
            did: $this->fakeDid,
            isPublicMode: false,
        );

        return $this->resolveStub($endpoint, 'POST_BLOB', null, null);
    }

    /**
     * Check public mode using fake DID instead of real sessions.
     */
    public function isPublicMode(): bool
    {
        return $this->fakeDid === null;
    }

    /**
     * Resolve a stub response for an endpoint.
     */
    protected function resolveStub(
        string $endpoint,
        string $method,
        ?array $params,
        ?array $body
    ): Response {
        // Try exact match first
        if (isset($this->stubs[$endpoint])) {
            return $this->resolveStubValue($this->stubs[$endpoint], $endpoint, $method, $params, $body);
        }

        // Try wildcard match
        foreach ($this->stubs as $pattern => $stub) {
            if (str_contains($pattern, '*') && $this->endpointMatchesPattern($endpoint, $pattern)) {
                return $this->resolveStubValue($stub, $endpoint, $method, $params, $body);
            }
        }

        // No stub found
        if ($this->preventStrayRequests) {
            throw new \RuntimeException(
                "Unexpected ATP request to [{$endpoint}]. " .
                'Either add a stub for this endpoint or call ->preventStrayRequests(false).'
            );
        }

        // Default: return empty success
        return FakeResponse::ok();
    }

    /**
     * Resolve a stub value to a Response.
     */
    protected function resolveStubValue(
        Response|array|\Closure|ResponseSequence $stub,
        string $endpoint,
        string $method,
        ?array $params,
        ?array $body
    ): Response {
        if ($stub instanceof ResponseSequence) {
            $response = $stub->next($endpoint);

            return $this->throwIfError($response, $endpoint);
        }

        if ($stub instanceof \Closure) {
            $result = $stub($endpoint, $params, $body, $method);

            if ($result instanceof Response) {
                return $this->throwIfError($result, $endpoint);
            }

            return FakeResponse::ok(is_array($result) ? $result : []);
        }

        if (is_array($stub)) {
            return FakeResponse::ok($stub);
        }

        return $this->throwIfError($stub, $endpoint);
    }

    /**
     * Throw AtpResponseException if the response is an error, mimicking real Client behavior.
     */
    protected function throwIfError(Response $response, string $endpoint): Response
    {
        if ($response->failed() || isset($response->json()['error'])) {
            throw new AtpResponseException(
                errorCode: $response->json('error', 'UnknownError'),
                errorMessage: $response->json('message', $response->body()),
                httpStatus: $response->status(),
                endpoint: $endpoint,
                responseBody: $response->toArray(),
            );
        }

        return $response;
    }

    /**
     * Check if an endpoint matches a wildcard pattern.
     */
    protected function endpointMatchesPattern(string $endpoint, string $pattern): bool
    {
        $regex = '/^' . str_replace('\*', '[^.]+', preg_quote($pattern, '/')) . '$/';

        return (bool) preg_match($regex, $endpoint);
    }

    /**
     * Override serviceUrl for fake mode.
     */
    public function serviceUrl(): string
    {
        return $this->serviceUrl ?? 'https://fake.bsky.social';
    }
}
