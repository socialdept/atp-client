<?php

namespace SocialDept\AtpClient\Auth;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use SocialDept\AtpClient\Data\Credentials;
use SocialDept\AtpClient\Data\DPoPKey;
use SocialDept\AtpClient\Data\RevocationResult;
use SocialDept\AtpClient\Enums\AuthType;
use SocialDept\AtpClient\Enums\RevocationFailure;
use SocialDept\AtpClient\Http\DPoPClient;
use SocialDept\AtpSupport\Facades\Resolver;
use Throwable;

class TokenRevoker
{
    public function __construct(
        protected DPoPClient $dpopClient,
        protected ClientAssertionManager $clientAssertion,
        protected AuthorizationServerDiscovery $discovery,
    ) {
    }

    /**
     * Revoke a session's tokens at the server. Never throws: every failure
     * comes back as a result, so the caller can still forget the session.
     */
    public function revoke(Credentials $credentials, ?DPoPKey $dpopKey): RevocationResult
    {
        try {
            return $credentials->authType === AuthType::Legacy
                ? $this->revokeLegacy($credentials)
                : $this->revokeOAuth($credentials, $dpopKey);
        } catch (Throwable $e) {
            return RevocationResult::failed($credentials->did, RevocationFailure::Unreachable, error: $e->getMessage());
        }
    }

    /**
     * RFC 7009 revocation at the authorization server that issued the tokens,
     * authenticated the way a refresh is: DPoP proof plus client assertion.
     *
     * Revoking the refresh token ends the grant, and the access token with it.
     * The access token is only sent when the server refuses the refresh token.
     */
    protected function revokeOAuth(Credentials $credentials, ?DPoPKey $dpopKey): RevocationResult
    {
        if (! $dpopKey) {
            return RevocationResult::failed($credentials->did, RevocationFailure::MissingDpopKey);
        }

        $metadata = $credentials->issuer
            ? $this->discovery->forAuthorizationServer($credentials->issuer)
            : $this->discovery->discover(Resolver::resolvePds($credentials->did));

        if (! $metadata->revocationEndpoint) {
            return RevocationResult::failed($credentials->did, RevocationFailure::NoEndpoint);
        }

        $tokens = array_filter([
            'refresh_token' => $credentials->refreshToken,
            'access_token' => $credentials->isExpired() ? null : $credentials->accessToken,
        ]);

        $response = null;

        foreach ($tokens as $hint => $token) {
            $response = $this->dpopClient->request($metadata->issuer, $metadata->revocationEndpoint, 'POST', $dpopKey)
                ->withRequestMiddleware($this->clientAssertion->refreshAssertionMiddleware($metadata->issuer))
                ->asForm()
                ->post($metadata->revocationEndpoint, array_merge(
                    $this->clientAssertion->getAuthParams($metadata->issuer),
                    [
                        'token' => $token,
                        'token_type_hint' => $hint,
                    ]
                ));

            if ($response->successful()) {
                return RevocationResult::revoked($credentials->did);
            }
        }

        return $response
            ? $this->failure($credentials->did, $response)
            : RevocationResult::failed($credentials->did, RevocationFailure::NoSession);
    }

    /**
     * App password sessions end with com.atproto.server.deleteSession, sent
     * with the refresh token.
     */
    protected function revokeLegacy(Credentials $credentials): RevocationResult
    {
        $pdsEndpoint = $credentials->issuer ?? Resolver::resolvePds($credentials->did);

        $response = Http::withHeader('Authorization', 'Bearer '.$credentials->refreshToken)
            ->withBody('', 'application/json')
            ->post(rtrim($pdsEndpoint, '/').'/xrpc/com.atproto.server.deleteSession');

        return $response->successful()
            ? RevocationResult::revoked($credentials->did)
            : $this->failure($credentials->did, $response);
    }

    protected function failure(string $did, Response $response): RevocationResult
    {
        $error = $response->json('error_description')
            ?? $response->json('message')
            ?? $response->json('error')
            ?? Str::limit($response->body(), 200);

        return RevocationResult::failed(
            $did,
            $response->serverError() || $response->status() === 429 ? RevocationFailure::Unreachable : RevocationFailure::Refused,
            $response->status(),
            is_string($error) ? $error : null,
        );
    }
}
