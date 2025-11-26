<?php

namespace SocialDept\AtpClient\Auth;

use SocialDept\AtpClient\Data\AccessToken;
use SocialDept\AtpClient\Data\DPoPKey;
use SocialDept\AtpClient\Exceptions\AuthenticationException;
use SocialDept\AtpClient\Http\DPoPClient;

class TokenRefresher
{
    public function __construct(
        protected DPoPClient $dpopClient,
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

        return AccessToken::fromResponse($response->json());
    }
}
