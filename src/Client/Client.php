<?php

namespace SocialDept\AtpClient\Client;

use BackedEnum;
use Illuminate\Support\Facades\Http;
use SocialDept\AtpClient\AtpClient;
use SocialDept\AtpClient\Exceptions\AtpResponseException;
use SocialDept\AtpClient\Http\DPoPClient;
use SocialDept\AtpClient\Http\HasHttp;
use SocialDept\AtpClient\Http\Response;
use SocialDept\AtpClient\Session\Session;
use SocialDept\AtpClient\Session\SessionManager;

class Client
{
    use HasHttp {
        call as authenticatedCall;
        postBlob as authenticatedPostBlob;
    }

    /**
     * The parent AtpClient instance we belong to
     */
    protected AtpClient $atp;

    /**
     * Service URL for public mode
     */
    protected ?string $serviceUrl;

    public function __construct(
        AtpClient $parent,
        ?SessionManager $sessions = null,
        ?string $did = null,
        ?string $serviceUrl = null,
    ) {
        $this->atp = $parent;
        $this->sessions = $sessions;
        $this->did = $did;
        $this->serviceUrl = $serviceUrl;

        if (! $this->isPublicMode()) {
            $this->dpopClient = app(DPoPClient::class);
        }
    }

    /**
     * Check if client is in public mode (no authentication).
     */
    public function isPublicMode(): bool
    {
        return $this->sessions === null || $this->did === null;
    }

    /**
     * Get the current session.
     */
    public function session(): Session
    {
        return $this->sessions->session($this->did);
    }

    /**
     * Get the service URL.
     */
    public function serviceUrl(): string
    {
        return $this->serviceUrl;
    }

    /**
     * Make XRPC call - routes to public or authenticated based on mode.
     */
    protected function call(
        string|BackedEnum $endpoint,
        string $method,
        ?array $params = null,
        ?array $body = null
    ): Response {
        if ($this->isPublicMode()) {
            return $this->publicCall($endpoint, $method, $params, $body);
        }

        return $this->authenticatedCall($endpoint, $method, $params, $body);
    }

    /**
     * Make public XRPC call (no authentication).
     */
    protected function publicCall(
        string|BackedEnum $endpoint,
        string $method,
        ?array $params = null,
        ?array $body = null
    ): Response {
        $endpoint = $endpoint instanceof BackedEnum ? $endpoint->value : $endpoint;
        $url = rtrim($this->serviceUrl, '/') . '/xrpc/' . $endpoint;
        $params = array_filter($params ?? [], fn ($v) => ! is_null($v));

        $response = match ($method) {
            'GET' => Http::get($url, $this->encodeQueryParams($params)),
            'POST' => Http::post($url, $body ?? $params),
            'DELETE' => Http::delete($url, $this->encodeQueryParams($params)),
            default => throw new \InvalidArgumentException("Unsupported method: {$method}"),
        };

        if ($response->failed() || isset($response->json()['error'])) {
            throw AtpResponseException::fromResponse($response, $endpoint);
        }

        return new Response($response);
    }

    /**
     * Make POST request with raw binary body (for blob uploads).
     * Only works in authenticated mode.
     */
    public function postBlob(string|BackedEnum $endpoint, string $data, string $mimeType): Response
    {
        if ($this->isPublicMode()) {
            throw new \RuntimeException('Blob uploads require authentication.');
        }

        return $this->authenticatedPostBlob($endpoint, $data, $mimeType);
    }
}
