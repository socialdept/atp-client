<?php

namespace SocialDept\AtpClient\Http;

use BackedEnum;
use GuzzleHttp\Psr7\Utils;
use Illuminate\Http\Client\Response as LaravelResponse;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use LogicException;
use Psr\Http\Message\StreamInterface;
use SocialDept\AtpClient\Auth\ScopeChecker;
use SocialDept\AtpClient\Enums\Scope;
use SocialDept\AtpClient\Exceptions\AtpResponseException;
use SocialDept\AtpClient\Exceptions\ValidationException;
use SocialDept\AtpClient\Session\Session;
use SocialDept\AtpClient\Session\SessionManager;
use SocialDept\AtpSchema\Facades\Schema;

trait HasHttp
{
    /**
     * Request headers a forwarded XRPC call may carry to the PDS. Everything
     * else, cookies and the caller's own Authorization included, is dropped.
     */
    public const FORWARDED_REQUEST_HEADERS = [
        'accept',
        'accept-language',
        'atproto-accept-labelers',
        'atproto-proxy',
        'content-type',
    ];

    /**
     * Request bodies above this many bytes are spooled to disk, not memory.
     */
    protected const FORWARD_MEMORY_LIMIT = 2 * 1024 * 1024;

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
        $params = array_filter($params ?? [], fn ($v) => ! is_null($v));

        $reactiveRefreshUsed = false;

        while (true) {
            $session = $this->sessions->ensureValid($this->did);
            $url = rtrim($session->pdsEndpoint(), '/').'/xrpc/'.$endpoint;

            $request = $this->buildAuthenticatedRequest($session, $url, $method);

            $response = match ($method) {
                'GET' => $request->get($url, $this->encodeQueryParams($params)),
                'POST' => $request->post($url, $body ?? $params),
                'DELETE' => $request->delete($url, $this->encodeQueryParams($params)),
                default => throw new InvalidArgumentException("Unsupported method: {$method}"),
            };

            if ($response->failed() || isset($response->json()['error'])) {
                // The access token can be stale before its stored window elapses
                // (expires_in is unreliable across PDSes). Force a refresh once and
                // replay. A dead grant surfaces as a terminal exception from refresh().
                if (! $reactiveRefreshUsed && $this->sessions !== null && $this->isStaleAccessResponse($response)) {
                    $reactiveRefreshUsed = true;
                    $this->sessions->refresh($this->did);

                    continue;
                }

                throw AtpResponseException::fromResponse($response, $endpoint);
            }

            if (config('atp-client.schema_validation') && Schema::exists($endpoint)) {
                $this->validateResponse($endpoint, $response);
            }

            return new Response($response);
        }
    }

    /**
     * A 401 whose error is not a DPoP-nonce challenge means the access token is
     * stale — a refresh-and-replay may recover it. `use_dpop_nonce` is handled by
     * the DPoP client's own retry and must not trigger a token refresh.
     */
    protected function isStaleAccessResponse(LaravelResponse $response): bool
    {
        if ($response->status() !== 401) {
            return false;
        }

        $error = strtolower((string) ($response->json()['error'] ?? ''));

        return $error !== 'use_dpop_nonce';
    }

    /**
     * Encode query parameters for AT Protocol XRPC requests.
     *
     * XRPC serializes boolean query parameters as the literal strings "true"
     * and "false". PHP's HTTP client would otherwise cast false to "0", which
     * the server rejects with "Expected boolean value type (got \"0\")".
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    protected function encodeQueryParams(array $params): array
    {
        return array_map(
            fn ($value) => is_bool($value) ? ($value ? 'true' : 'false') : $value,
            $params,
        );
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
     * Forward an XRPC call to the user's PDS as-is, authenticated with the
     * stored session, and return the PDS's response untouched.
     *
     * This is the plumbing for a same-origin passthrough: a browser that holds
     * only a session cookie sends ordinary XRPC requests to the app, and the
     * app forwards them with its DPoP-bound token. Headers such as
     * `atproto-proxy` survive, so service proxying works the same as from a
     * client holding the token itself.
     *
     * Unlike get() and post(), an error from the PDS is returned rather than
     * thrown, so it can be relayed to the caller. A stale access token is still
     * refreshed once and the call replayed. The response body is streamed:
     * read it in chunks, or hand it to ForwardedResponse::toSymfony().
     *
     * @param  string  $method  GET or POST
     * @param  string|null  $query  The raw query string, passed through verbatim so
     *                              repeated parameters keep XRPC's encoding
     * @param  StreamInterface|resource|string|null  $body
     * @param  array<string, string|string[]>  $headers  Filtered to FORWARDED_REQUEST_HEADERS
     */
    public function forward(
        string $method,
        string|BackedEnum $endpoint,
        ?string $query = null,
        mixed $body = null,
        array $headers = [],
    ): LaravelResponse {
        if ($this->sessions === null || $this->did === null) {
            throw new LogicException('Forwarding needs an authenticated client: use Atp::as($did).');
        }

        $method = strtoupper($method);

        if (! in_array($method, ['GET', 'POST'], true)) {
            throw new InvalidArgumentException("XRPC only uses GET and POST, got {$method}.");
        }

        $endpoint = $endpoint instanceof BackedEnum ? $endpoint->value : $endpoint;
        $headers = $this->forwardableHeaders($headers);
        $stream = $method === 'POST' ? $this->spoolBody($body) : null;

        // The body sets its own Content-Type, so sending it as a header too
        // would duplicate it.
        $contentType = $headers['content-type'] ?? 'application/octet-stream';
        unset($headers['content-type']);

        $reactiveRefreshUsed = false;

        while (true) {
            $session = $this->sessions->ensureValid($this->did);
            $url = rtrim($session->pdsEndpoint(), '/').'/xrpc/'.$endpoint;

            $request = $this->buildAuthenticatedRequest($session, $url, $method)
                ->withHeaders($headers)
                ->withOptions(array_filter([
                    'stream' => true,
                    // Kept out of the URL so the DPoP proof's htu stays query-free.
                    'query' => $query !== null && $query !== '' ? $query : null,
                ], fn ($value) => $value !== null));

            if ($stream !== null) {
                $stream->rewind();
                $request = $request->withBody($stream, $contentType);
            }

            $response = $method === 'GET' ? $request->get($url) : $request->post($url);

            // Deciding whether a 401 is a stale token means reading its body,
            // which would leave a streamed response empty for the caller.
            // Error bodies are small, so buffer just those.
            if ($response->status() === 401) {
                $psr = $response->toPsrResponse();
                $response = new LaravelResponse($psr->withBody(Utils::streamFor((string) $psr->getBody())));
            }

            if (! $reactiveRefreshUsed && $this->isStaleAccessResponse($response)) {
                $reactiveRefreshUsed = true;
                $this->sessions->refresh($this->did);

                continue;
            }

            return $response;
        }
    }

    /**
     * Keep only the headers a forwarded call may carry, lower-cased.
     *
     * @param  array<string, string|string[]>  $headers
     * @return array<string, string>
     */
    protected function forwardableHeaders(array $headers): array
    {
        $kept = [];

        foreach ($headers as $name => $value) {
            $name = strtolower((string) $name);

            if (in_array($name, self::FORWARDED_REQUEST_HEADERS, true)) {
                $kept[$name] = is_array($value) ? implode(', ', $value) : (string) $value;
            }
        }

        return $kept;
    }

    /**
     * Copy a request body into a rewindable temporary stream.
     *
     * A DPoP nonce challenge or a token refresh replays the request, and a
     * one-shot stream such as php://input cannot be read twice. php://temp
     * keeps small bodies in memory and spills larger ones to disk.
     *
     * @param  StreamInterface|resource|string|null  $body
     */
    protected function spoolBody(mixed $body): StreamInterface
    {
        $spool = Utils::streamFor(fopen('php://temp/maxmemory:'.self::FORWARD_MEMORY_LIMIT, 'r+b'));

        if ($body !== null && $body !== '') {
            Utils::copyToStream(Utils::streamFor($body), $spool);
        }

        $spool->rewind();

        return $spool;
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
