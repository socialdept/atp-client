<?php

namespace SocialDept\AtpClient\Auth;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use SocialDept\AtpClient\Data\AccessToken;
use SocialDept\AtpClient\Data\DPoPKey;
use SocialDept\AtpClient\Enums\AuthType;
use SocialDept\AtpClient\Exceptions\AuthenticationException;
use SocialDept\AtpClient\Exceptions\OAuthSessionInvalidException;
use SocialDept\AtpClient\Exceptions\TransientAuthFailureException;
use SocialDept\AtpClient\Http\DPoPClient;

class TokenRefresher
{
    protected int $maxRetries = 2;

    protected int $retryDelayMs = 500;

    public function __construct(
        protected DPoPClient $dpopClient,
        protected ClientAssertionManager $clientAssertion,
        protected AuthorizationServerDiscovery $discovery,
    ) {}

    /**
     * Refresh access token using refresh token.
     * NOTE: Refresh tokens are single-use!
     *
     * @param  string  $pdsEndpoint  The user's PDS endpoint (NOT the auth server — discovery resolves that)
     *
     * @throws OAuthSessionInvalidException When refresh token is missing or empty
     * @throws TransientAuthFailureException When a transient server error occurs (5xx, HTML response, etc.)
     * @throws AuthenticationException When token refresh fails permanently (4xx with JSON error)
     */
    public function refresh(
        string $refreshToken,
        string $pdsEndpoint,
        DPoPKey $dpopKey,
        ?string $handle = null,
        AuthType $authType = AuthType::OAuth,
    ): AccessToken {
        if (empty($refreshToken)) {
            throw OAuthSessionInvalidException::missingRefreshToken();
        }

        return $authType === AuthType::Legacy
            ? $this->refreshLegacy($refreshToken, $pdsEndpoint, $handle)
            : $this->refreshOAuth($refreshToken, $pdsEndpoint, $dpopKey, $handle);
    }

    /**
     * Refresh OAuth session using the discovered token endpoint with DPoP.
     * Retries transient failures with exponential backoff.
     */
    protected function refreshOAuth(
        string $refreshToken,
        string $pdsEndpoint,
        DPoPKey $dpopKey,
        ?string $handle,
    ): AccessToken {
        $metadata = $this->discovery->discover($pdsEndpoint);
        $tokenUrl = $metadata->tokenEndpoint;
        $issuer = $metadata->issuer;

        for ($attempt = 0; $attempt <= $this->maxRetries; $attempt++) {
            $response = $this->dpopClient->request($issuer, $tokenUrl, 'POST', $dpopKey)
                ->asForm()
                ->post($tokenUrl, array_merge(
                    $this->clientAssertion->getAuthParams($issuer),
                    [
                        'grant_type' => 'refresh_token',
                        'refresh_token' => $refreshToken,
                    ]
                ));

            if ($response->successful()) {
                return AccessToken::fromResponse($response->json(), $handle, $issuer);
            }

            if (! $this->isTransientFailure($response)) {
                throw new AuthenticationException('Token refresh failed: '.$response->body());
            }

            // Last attempt — don't sleep, just throw
            if ($attempt === $this->maxRetries) {
                break;
            }

            usleep($this->retryDelayMs * 1000 * ($attempt + 1));
        }

        throw TransientAuthFailureException::fromResponse($response->body(), $response->status());
    }

    /**
     * Refresh legacy session using /xrpc/com.atproto.server.refreshSession endpoint.
     * Retries transient failures with exponential backoff.
     */
    protected function refreshLegacy(
        string $refreshToken,
        string $pdsEndpoint,
        ?string $handle,
    ): AccessToken {
        for ($attempt = 0; $attempt <= $this->maxRetries; $attempt++) {
            $response = Http::withHeader('Authorization', 'Bearer '.$refreshToken)
                ->withBody('', 'application/json')
                ->post($pdsEndpoint.'/xrpc/com.atproto.server.refreshSession');

            if ($response->successful()) {
                return AccessToken::fromResponse($response->json(), $handle, $pdsEndpoint);
            }

            if (! $this->isTransientFailure($response)) {
                throw new AuthenticationException('Token refresh failed: '.$response->body());
            }

            if ($attempt === $this->maxRetries) {
                break;
            }

            usleep($this->retryDelayMs * 1000 * ($attempt + 1));
        }

        throw TransientAuthFailureException::fromResponse($response->body(), $response->status());
    }

    /**
     * Determine if a failed response is a transient (retryable) failure.
     *
     * Transient: 5xx errors, HTML responses (proxy/CDN errors), 0 status (network failure)
     * Permanent: 4xx errors with JSON body (invalid_grant, expired_token, etc.)
     */
    protected function isTransientFailure(Response $response): bool
    {
        $status = $response->status();

        // Network failures or server errors are transient
        if ($status === 0 || $status >= 500) {
            return true;
        }

        // HTML response from a proxy/CDN instead of the actual API is transient
        $body = $response->body();
        if (str_contains($body, '<!DOCTYPE') || str_contains($body, '<html')) {
            return true;
        }

        return false;
    }
}
