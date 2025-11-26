<?php

namespace SocialDept\AtpClient\Http;

use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Response as LaravelResponse;
use InvalidArgumentException;
use SocialDept\AtpClient\Auth\DPoPKeyManager;
use SocialDept\AtpClient\Auth\DPoPNonceManager;
use SocialDept\AtpClient\Exceptions\ValidationException;
use SocialDept\AtpClient\Session\SessionManager;
use SocialDept\AtpSchema\Facades\Schema;

trait HasHttp
{
    protected SessionManager $sessions;

    protected Factory $http;

    protected string $identifier;

    protected DPoPNonceManager $nonceManager;

    /**
     * Make XRPC call
     */
    protected function call(
        string $endpoint,
        string $method,
        ?array $params = null,
        ?array $body = null
    ): Response {
        // Ensure session is valid (auto-refresh)
        $session = $this->sessions->ensureValid($this->identifier);

        // Build URL
        $url = rtrim($session->pdsEndpoint(), '/').'/xrpc/'.$endpoint;

        // Get DPoP nonce
        $nonce = $this->nonceManager->getNonce($session->pdsEndpoint());

        // Create DPoP proof using DPoPKeyManager
        $dpopProof = app(DPoPKeyManager::class)->createProof(
            key: $session->dpopKey(),
            method: $method,
            url: $url,
            nonce: $nonce,
            accessToken: $session->accessToken(),
        );

        // Filter null parameters
        $params = array_filter($params ?? [], fn ($v) => ! is_null($v));

        // Build request
        $request = $this->http
            ->withHeaders([
                'Authorization' => 'Bearer '.$session->accessToken(),
                'DPoP' => $dpopProof,
            ]);

        // Send request
        $response = match ($method) {
            'GET' => $request->get($url, $params),
            'POST' => $request->post($url, $body ?? $params),
            'DELETE' => $request->delete($url, $params),
            default => throw new InvalidArgumentException("Unsupported method: {$method}"),
        };

        // Store nonce from response if present
        if ($newNonce = $response->header('DPoP-Nonce')) {
            $this->nonceManager->storeNonce($session->pdsEndpoint(), $newNonce);
        }

        // Validate response if schema exists
        if (Schema::exists($endpoint)) {
            $this->validateResponse($endpoint, $response);
        }

        return new Response($response);
    }

    /**
     * Validate response against schema
     */
    protected function validateResponse(string $endpoint, LaravelResponse $response): void
    {
        if (! $response->successful()) {
            return; // Don't validate error responses
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
        // Ensure session is valid (auto-refresh)
        $session = $this->sessions->ensureValid($this->identifier);

        // Build URL
        $url = rtrim($session->pdsEndpoint(), '/').'/xrpc/'.$endpoint;

        // Get DPoP nonce
        $nonce = $this->nonceManager->getNonce($session->pdsEndpoint());

        // Create DPoP proof using DPoPKeyManager
        $dpopProof = app(DPoPKeyManager::class)->createProof(
            key: $session->dpopKey(),
            method: 'POST',
            url: $url,
            nonce: $nonce,
            accessToken: $session->accessToken(),
        );

        // Build and send request with raw binary body
        $response = $this->http
            ->withHeaders([
                'Authorization' => 'Bearer '.$session->accessToken(),
                'DPoP' => $dpopProof,
            ])
            ->withBody($data, $mimeType)
            ->post($url);

        // Store nonce from response if present
        if ($newNonce = $response->header('DPoP-Nonce')) {
            $this->nonceManager->storeNonce($session->pdsEndpoint(), $newNonce);
        }

        return new Response($response);
    }
}
