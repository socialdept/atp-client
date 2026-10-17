<?php

namespace SocialDept\AtpClient\Testing;

use SocialDept\AtpClient\Auth\OAuthEngine;
use SocialDept\AtpClient\Data\AccessToken;
use SocialDept\AtpClient\Data\AuthorizationRequest;
use SocialDept\AtpClient\Data\DPoPKey;

class FakeOAuthEngine extends OAuthEngine
{
    protected array $recordedCalls = [];

    protected ?AuthorizationRequest $nextAuthorizationRequest = null;

    protected ?AccessToken $nextAccessToken = null;

    /**
     * No-op constructor — we don't need real auth services.
     */
    public function __construct()
    {
    }

    /**
     * Fake authorize — returns a pre-configured or default request.
     */
    public function authorize(
        string $identifier,
        ?array $scopes = null,
        ?string $pdsEndpoint = null,
        ?string $prompt = null,
    ): AuthorizationRequest {
        $this->recordedCalls[] = ['authorize', compact('identifier', 'scopes', 'pdsEndpoint', 'prompt')];

        if ($this->nextAuthorizationRequest) {
            return $this->nextAuthorizationRequest;
        }

        return new AuthorizationRequest(
            url: 'https://bsky.social/oauth/authorize?state=fake-state',
            state: 'fake-state',
            codeVerifier: 'fake-code-verifier',
            dpopKey: self::fakeDPoPKey(),
            requestUri: 'urn:ietf:params:oauth:request_uri:fake',
            pdsEndpoint: $pdsEndpoint ?? 'https://pds.bsky.social',
            handle: $identifier,
            authServerIssuer: 'https://bsky.social',
            tokenEndpoint: 'https://bsky.social/oauth/token',
        );
    }

    /**
     * Fake authorizeAt, starting at a server without naming an account.
     */
    public function authorizeAt(string $server, ?array $scopes = null, ?string $prompt = null): AuthorizationRequest
    {
        $this->recordedCalls[] = ['authorizeAt', compact('server', 'scopes', 'prompt')];

        return $this->nextAuthorizationRequest ?? new AuthorizationRequest(
            url: rtrim($server, '/').'/oauth/authorize?request_uri=fake',
            state: 'fake-state',
            codeVerifier: 'fake-code-verifier',
            dpopKey: self::fakeDPoPKey(),
            requestUri: 'urn:ietf:params:oauth:request_uri:fake',
            pdsEndpoint: rtrim($server, '/'),
            handle: null,
            authServerIssuer: rtrim($server, '/'),
            tokenEndpoint: rtrim($server, '/').'/oauth/token',
        );
    }

    /**
     * Fake callback — returns a pre-configured or default token.
     */
    public function callback(
        string $code,
        string $state,
        AuthorizationRequest $request,
        ?string $issuer = null,
    ): AccessToken {
        $this->recordedCalls[] = ['callback', compact('code', 'state', 'issuer')];

        if ($this->nextAccessToken) {
            return $this->nextAccessToken;
        }

        return new AccessToken(
            accessJwt: 'fake-access-jwt',
            refreshJwt: 'fake-refresh-jwt',
            did: 'did:plc:fake123',
            expiresAt: now()->addHour(),
            handle: $request->handle ?? 'test.bsky.social',
            issuer: $request->authServerIssuer,
            scope: ['atproto', 'transition:generic'],
        );
    }

    /**
     * Configure the next authorization request to return.
     */
    public function willReturnAuthorizationRequest(AuthorizationRequest $request): static
    {
        $this->nextAuthorizationRequest = $request;

        return $this;
    }

    /**
     * Configure the next access token to return from callback.
     */
    public function willReturnAccessToken(AccessToken $token): static
    {
        $this->nextAccessToken = $token;

        return $this;
    }

    /**
     * Get all recorded OAuth calls.
     */
    public function recordedCalls(): array
    {
        return $this->recordedCalls;
    }

    /**
     * Generate a fake DPoP key for testing using phpseclib (same as the real package).
     */
    public static function fakeDPoPKey(): DPoPKey
    {
        $privateKey = \phpseclib3\Crypt\EC::createKey('secp256r1');

        return new DPoPKey(
            privateKey: $privateKey,
            publicKey: $privateKey->getPublicKey(),
            keyId: 'fake-key-' . bin2hex(random_bytes(8)),
        );
    }
}
