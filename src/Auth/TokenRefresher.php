<?php

namespace SocialDept\AtpClient\Auth;

use Illuminate\Http\Client\Factory as HttpClient;
use SocialDept\AtpClient\Data\AccessToken;
use SocialDept\AtpClient\Data\DPoPKey;
use SocialDept\AtpClient\Exceptions\AuthenticationException;

class TokenRefresher
{
    public function __construct(
        protected HttpClient $http,
        protected DPoPKeyManager $dpopManager,
    ) {}

    /**
     * Refresh access token using refresh token
     * NOTE: Refresh tokens are single-use!
     */
    public function refresh(
        string $refreshToken,
        string $pdsEndpoint,
        DPoPKey $dpopKey
    ): AccessToken {
        $dpopProof = $this->dpopManager->createProof(
            key: $dpopKey,
            method: 'POST',
            url: $pdsEndpoint.'/oauth/token',
            nonce: $this->getDpopNonce($pdsEndpoint),
        );

        $response = $this->http
            ->withHeaders([
                'DPoP' => $dpopProof,
                'Content-Type' => 'application/x-www-form-urlencoded',
            ])
            ->asForm()
            ->post($pdsEndpoint.'/oauth/token', [
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken,
            ]);

        if ($response->failed()) {
            throw new AuthenticationException(
                'Token refresh failed: '.$response->body()
            );
        }

        return AccessToken::fromResponse($response->json());
    }

    protected function getDpopNonce(string $pdsEndpoint): string
    {
        // TODO: Implement proper DPoP nonce fetching and caching
        // For now, return a placeholder that will need to be replaced
        return 'temp-nonce-'.time();
    }
}
