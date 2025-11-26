<?php

namespace SocialDept\AtpClient\Auth;

use Illuminate\Support\Facades\Http;
use SocialDept\AtpClient\Data\AccessToken;
use SocialDept\AtpClient\Data\DPoPKey;
use SocialDept\AtpClient\Enums\AuthType;
use SocialDept\AtpClient\Exceptions\AuthenticationException;
use SocialDept\AtpClient\Http\DPoPClient;

class TokenRefresher
{
    public function __construct(
        protected DPoPClient $dpopClient,
    ) {}

    /**
     * Refresh access token using refresh token.
     * NOTE: Refresh tokens are single-use!
     */
    public function refresh(
        string $refreshToken,
        string $pdsEndpoint,
        DPoPKey $dpopKey,
        ?string $handle = null,
        AuthType $authType = AuthType::OAuth,
    ): AccessToken {
        return $authType === AuthType::Legacy
            ? $this->refreshLegacy($refreshToken, $pdsEndpoint, $handle)
            : $this->refreshOAuth($refreshToken, $pdsEndpoint, $dpopKey, $handle);
    }

    /**
     * Refresh OAuth session using /oauth/token endpoint with DPoP.
     */
    protected function refreshOAuth(
        string $refreshToken,
        string $pdsEndpoint,
        DPoPKey $dpopKey,
        ?string $handle,
    ): AccessToken {
        $tokenUrl = $pdsEndpoint.'/oauth/token';

        $response = $this->dpopClient->request($pdsEndpoint, $tokenUrl, 'POST', $dpopKey)
            ->asForm()
            ->post($tokenUrl, [
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken,
            ]);

        if ($response->failed()) {
            throw new AuthenticationException('Token refresh failed: '.$response->body());
        }

        return AccessToken::fromResponse($response->json(), $handle, $pdsEndpoint);
    }

    /**
     * Refresh legacy session using /xrpc/com.atproto.server.refreshSession endpoint.
     */
    protected function refreshLegacy(
        string $refreshToken,
        string $pdsEndpoint,
        ?string $handle,
    ): AccessToken {
        $response = Http::withHeader('Authorization', 'Bearer '.$refreshToken)
            ->post($pdsEndpoint.'/xrpc/com.atproto.server.refreshSession');

        if ($response->failed()) {
            throw new AuthenticationException('Token refresh failed: '.$response->body());
        }

        return AccessToken::fromResponse($response->json(), $handle, $pdsEndpoint);
    }
}
