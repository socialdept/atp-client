<?php

namespace SocialDept\AtpClient\Http;

use BackedEnum;
use Illuminate\Http\Client\Response as LaravelResponse;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use SocialDept\AtpClient\Auth\ScopeChecker;
use SocialDept\AtpClient\Enums\Scope;
use SocialDept\AtpClient\Exceptions\AtpResponseException;
use SocialDept\AtpClient\Exceptions\ValidationException;
use SocialDept\AtpClient\Session\Session;
use SocialDept\AtpClient\Session\SessionManager;
use SocialDept\AtpSchema\Facades\Schema;

trait HasHttp
{
    protected ?SessionManager $sessions = null;

    protected ?string $did = null;

    protected ?DPoPClient $dpopClient = null;

    protected ?ScopeChecker $scopeChecker = null;

    /**
     * Make XRPC call
     */
    protected function call(
        string|BackedEnum $endpoint,
        string $method,
        ?array $params = null,
        ?array $body = null
    ): Response {
        $endpoint = $endpoint instanceof BackedEnum ? $endpoint->value : $endpoint;
        $session = $this->sessions->ensureValid($this->did);
        $url = rtrim($session->pdsEndpoint(), '/').'/xrpc/'.$endpoint;

        $params = array_filter($params ?? [], fn ($v) => ! is_null($v));

        $request = $this->buildAuthenticatedRequest($session, $url, $method);

        $response = match ($method) {
            'GET' => $request->get($url, $params),
            'POST' => $request->post($url, $body ?? $params),
            'DELETE' => $request->delete($url, $params),
            default => throw new InvalidArgumentException("Unsupported method: {$method}"),
        };

        if ($response->failed() || isset($response->json()['error'])) {
            throw AtpResponseException::fromResponse($response, $endpoint);
        }

        if (config('atp-client.schema_validation') && Schema::exists($endpoint)) {
            $this->validateResponse($endpoint, $response);
        }

        return new Response($response);
    }

    /**
     * Build authenticated request.
     *
     * OAuth sessions use DPoP proof with Bearer token.
     * Legacy sessions use plain Bearer token.
     */
    protected function buildAuthenticatedRequest(
        Session $session,
        string $url,
        string $method
    ): \Illuminate\Http\Client\PendingRequest {
        if ($session->isLegacy()) {
            return Http::withHeader('Authorization', 'Bearer '.$session->accessToken());
        }

        return $this->dpopClient->request(
            pdsEndpoint: $session->pdsEndpoint(),
            url: $url,
            method: $method,
            dpopKey: $session->dpopKey(),
            accessToken: $session->accessToken(),
        );
    }

    /**
     * Validate response against schema
     */
    protected function validateResponse(string $endpoint, LaravelResponse $response): void
    {
        if (! $response->successful()) {
            return;
        }

        $data = $response->json();

        $errors = Schema::validateWithErrors($endpoint, $data);

        if (! empty($errors)) {
            throw new ValidationException($errors);
        }
    }

    /**
     * Make GET request
     */
    public function get(string|BackedEnum $endpoint, array $params = []): Response
    {
        return $this->call($endpoint, 'GET', $params);
    }

    /**
     * Make POST request
     */
    public function post(string|BackedEnum $endpoint, array $body = []): Response
    {
        return $this->call($endpoint, 'POST', null, $body);
    }

    /**
     * Make DELETE request
     */
    public function delete(string|BackedEnum $endpoint, array $params = []): Response
    {
        return $this->call($endpoint, 'DELETE', $params);
    }

    /**
     * Make POST request with raw binary body (for blob uploads)
     */
    public function postBlob(string|BackedEnum $endpoint, string $data, string $mimeType): Response
    {
        $endpoint = $endpoint instanceof BackedEnum ? $endpoint->value : $endpoint;
        $session = $this->sessions->ensureValid($this->did);
        $url = rtrim($session->pdsEndpoint(), '/').'/xrpc/'.$endpoint;

        $response = $this->buildAuthenticatedRequest($session, $url, 'POST')
            ->withBody($data, $mimeType)
            ->post($url);

        if ($response->failed() || isset($response->json()['error'])) {
            throw AtpResponseException::fromResponse($response, $endpoint);
        }

        return new Response($response);
    }

    /**
     * Require specific scopes before making a request.
     *
     * Checks if the session has the required scopes. In strict mode, throws
     * MissingScopeException if scopes are missing. In permissive mode, logs
     * a warning but allows the request to proceed.
     *
     * @param  string|Scope  ...$scopes  The required scopes
     *
     * @throws \SocialDept\AtpClient\Exceptions\MissingScopeException
     */
    protected function requireScopes(string|Scope ...$scopes): void
    {
        $session = $this->sessions->session($this->did);

        $this->getScopeChecker()->checkOrFail($session, $scopes);
    }

    /**
     * Check if the session has a specific scope.
     */
    protected function hasScope(string|Scope $scope): bool
    {
        $session = $this->sessions->session($this->did);

        return $this->getScopeChecker()->hasScope($session, $scope);
    }

    /**
     * Get the scope checker instance.
     */
    protected function getScopeChecker(): ScopeChecker
    {
        if ($this->scopeChecker === null) {
            $this->scopeChecker = app(ScopeChecker::class);
        }

        return $this->scopeChecker;
    }
}
