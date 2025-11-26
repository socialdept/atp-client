<?php

namespace SocialDept\AtpClient\Http;

use Illuminate\Http\Client\Response as LaravelResponse;
use InvalidArgumentException;
use SocialDept\AtpClient\Exceptions\ValidationException;
use SocialDept\AtpClient\Session\Session;
use SocialDept\AtpClient\Session\SessionManager;
use SocialDept\AtpSchema\Facades\Schema;

trait HasHttp
{
    protected SessionManager $sessions;

    protected string $identifier;

    protected DPoPClient $dpopClient;

    /**
     * Make XRPC call
     */
    protected function call(
        string $endpoint,
        string $method,
        ?array $params = null,
        ?array $body = null
    ): Response {
        $session = $this->sessions->ensureValid($this->identifier);
        $url = rtrim($session->pdsEndpoint(), '/').'/xrpc/'.$endpoint;

        $params = array_filter($params ?? [], fn ($v) => ! is_null($v));

        $request = $this->buildAuthenticatedRequest($session, $url, $method);

        $response = match ($method) {
            'GET' => $request->get($url, $params),
            'POST' => $request->post($url, $body ?? $params),
            'DELETE' => $request->delete($url, $params),
            default => throw new InvalidArgumentException("Unsupported method: {$method}"),
        };

        if (Schema::exists($endpoint)) {
            $this->validateResponse($endpoint, $response);
        }

        return new Response($response);
    }

    /**
     * Build authenticated request with DPoP proof and automatic nonce retry
     */
    protected function buildAuthenticatedRequest(
        Session $session,
        string $url,
        string $method
    ): \Illuminate\Http\Client\PendingRequest {
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

        if (! Schema::validate($endpoint, $data)) {
            $errors = Schema::getErrors($endpoint, $data);
            throw new ValidationException($errors);
        }
    }

    /**
     * Make GET request
     */
    protected function get(string $endpoint, array $params = []): Response
    {
        return $this->call($endpoint, 'GET', $params);
    }

    /**
     * Make POST request
     */
    protected function post(string $endpoint, array $body = []): Response
    {
        return $this->call($endpoint, 'POST', null, $body);
    }

    /**
     * Make DELETE request
     */
    protected function delete(string $endpoint, array $params = []): Response
    {
        return $this->call($endpoint, 'DELETE', $params);
    }

    /**
     * Make POST request with raw binary body (for blob uploads)
     */
    protected function postBlob(string $endpoint, string $data, string $mimeType): Response
    {
        $session = $this->sessions->ensureValid($this->identifier);
        $url = rtrim($session->pdsEndpoint(), '/').'/xrpc/'.$endpoint;

        $response = $this->buildAuthenticatedRequest($session, $url, 'POST')
            ->withBody($data, $mimeType)
            ->post($url);

        return new Response($response);
    }
}
