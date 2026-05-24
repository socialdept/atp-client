<?php

namespace SocialDept\AtpClient\Auth;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use SocialDept\AtpClient\Contracts\KeyStore;
use SocialDept\AtpClient\Data\AccessToken;
use SocialDept\AtpClient\Data\AuthorizationRequest;
use SocialDept\AtpClient\Data\AuthorizationServerMetadata;
use SocialDept\AtpClient\Data\DPoPKey;
use SocialDept\AtpClient\Events\SessionAuthenticated;
use SocialDept\AtpClient\Exceptions\AuthenticationException;
use SocialDept\AtpClient\Exceptions\OAuthSessionInvalidException;
use SocialDept\AtpClient\Http\DPoPClient;
use SocialDept\AtpSupport\Facades\Resolver;

class OAuthEngine
{
    public function __construct(
        protected DPoPKeyManager $dpopManager,
        protected ClientMetadataManager $metadata,
        protected DPoPClient $dpopClient,
        protected ClientAssertionManager $clientAssertion,
        protected KeyStore $keyStore,
        protected AuthorizationServerDiscovery $discovery,
    ) {}

    /**
     * Initiate OAuth flow
     */
    public function authorize(
        string $identifier,
        ?array $scopes = null,
        ?string $pdsEndpoint = null
    ): AuthorizationRequest {
        // Use configured scopes if none provided
        $scopes = $scopes ?? $this->metadata->getScopes();

        // Resolve PDS endpoint
        if (! $pdsEndpoint) {
            $pdsEndpoint = Resolver::resolvePds($identifier);
        }

        // Discover authorization server (handles mushroom PDSes and self-hosted)
        $authServer = $this->discovery->discover($pdsEndpoint);

        // Generate PKCE challenge
        $codeVerifier = Str::random(128);
        $codeChallenge = $this->generatePkceChallenge($codeVerifier);

        // Generate state
        $state = Str::random(32);

        // Generate DPoP key for this flow
        $dpopKey = $this->dpopManager->generateKey('oauth_'.$state);

        // Build PAR request using discovered endpoints
        $parResponse = $this->pushAuthorizationRequest(
            $authServer,
            $scopes,
            $codeChallenge,
            $state,
            $dpopKey
        );

        // Build authorization URL using discovered endpoint
        $authUrl = $authServer->authorizationEndpoint.'?'.http_build_query([
            'request_uri' => $parResponse['request_uri'],
            'client_id' => $this->metadata->getClientId(),
        ]);

        return new AuthorizationRequest(
            url: $authUrl,
            state: $state,
            codeVerifier: $codeVerifier,
            dpopKey: $dpopKey,
            requestUri: $parResponse['request_uri'],
            pdsEndpoint: $pdsEndpoint,
            handle: $identifier,
            authServerIssuer: $authServer->issuer,
            tokenEndpoint: $authServer->tokenEndpoint,
        );
    }

    /**
     * Complete OAuth flow with authorization code
     */
    public function callback(
        string $code,
        string $state,
        AuthorizationRequest $request
    ): AccessToken {
        if ($state !== $request->state) {
            throw new AuthenticationException('State mismatch');
        }

        // Use the stored token endpoint from discovery (handles mushroom PDSes)
        // Fall back to PDS endpoint for backwards compatibility with cached requests
        $authServerIssuer = $request->authServerIssuer ?? $request->pdsEndpoint;
        $tokenUrl = $request->tokenEndpoint ?? $request->pdsEndpoint.'/oauth/token';

        $response = $this->dpopClient->request($authServerIssuer, $tokenUrl, 'POST', $request->dpopKey)
            ->withRequestMiddleware($this->clientAssertion->refreshAssertionMiddleware($authServerIssuer))
            ->asForm()
            ->post($tokenUrl, array_merge(
                $this->clientAssertion->getAuthParams($authServerIssuer),
                [
                    'grant_type' => 'authorization_code',
                    'code' => $code,
                    'redirect_uri' => $this->metadata->getRedirectUris()[0] ?? null,
                    'code_verifier' => $request->codeVerifier,
                ]
            ));

        if ($response->failed()) {
            throw new AuthenticationException('Token exchange failed: '.$response->body());
        }

        // Store auth server issuer for token refresh operations
        $token = AccessToken::fromResponse($response->json(), $request->handle, $authServerIssuer);

        // Store the DPoP key with the session ID so future requests can use it
        // The token is bound to this key's thumbprint (cnf.jkt claim)
        $sessionId = 'session_'.hash('sha256', $token->did);
        $this->keyStore->store($sessionId, $request->dpopKey);

        event(new SessionAuthenticated($token));

        return $token;
    }

    /**
     * Prepare authorization parameters for client-side PAR.
     *
     * Resolves the user's PDS, discovers the authorization server,
     * generates PKCE + state, and returns the metadata the caller
     * needs to perform PAR themselves (e.g. client-side with a
     * non-extractable DPoP key).
     */
    public function prepareAuthorization(
        string $identifier,
        ?array $scopes = null,
        ?string $pdsEndpoint = null,
    ): array {
        $scopes = $scopes ?? $this->metadata->getScopes();

        if (! $pdsEndpoint) {
            $pdsEndpoint = Resolver::resolvePds($identifier);
        }

        $authServer = $this->discovery->discover($pdsEndpoint);

        $codeVerifier = Str::random(128);
        $codeChallenge = $this->generatePkceChallenge($codeVerifier);
        $state = Str::random(32);

        return [
            'state' => $state,
            'codeVerifier' => $codeVerifier,
            'codeChallenge' => $codeChallenge,
            'codeChallengeMethod' => 'S256',
            'scopes' => implode(' ', $scopes),
            'parEndpoint' => $authServer->parEndpoint,
            'authorizationEndpoint' => $authServer->authorizationEndpoint,
            'issuer' => $authServer->issuer,
            'tokenEndpoint' => $authServer->tokenEndpoint,
            'redirectUri' => $this->metadata->getRedirectUris()[0] ?? null,
            'clientId' => $this->metadata->getClientId(),
            'pdsEndpoint' => $pdsEndpoint,
            'handle' => $identifier,
        ];
    }

    /**
     * Validate the OAuth callback but return the authorization code
     * and metadata without exchanging it for tokens.
     *
     * This allows the caller to perform the token exchange themselves
     * (e.g. client-side with a non-extractable DPoP key).
     *
     * @return array{code: string, codeVerifier: string, redirectUri: string|null, issuer: string, tokenEndpoint: string, pdsEndpoint: string, handle: string|null}
     */
    public function callbackWithoutExchange(
        string $code,
        string $state,
        AuthorizationRequest $request,
    ): array {
        if ($state !== $request->state) {
            throw new AuthenticationException('State mismatch');
        }

        $authServerIssuer = $request->authServerIssuer ?? $request->pdsEndpoint;
        $tokenEndpoint = $request->tokenEndpoint ?? $authServerIssuer.'/oauth/token';

        return [
            'code' => $code,
            'codeVerifier' => $request->codeVerifier,
            'redirectUri' => $this->metadata->getRedirectUris()[0] ?? null,
            'issuer' => $authServerIssuer,
            'tokenEndpoint' => $tokenEndpoint,
            'pdsEndpoint' => $request->pdsEndpoint,
            'handle' => $request->handle,
        ];
    }

    /**
     * Initiate OAuth authorization for re-authentication.
     *
     * Unlike regular authorize(), this caches the request keyed by the original
     * state parameter, allowing the callback to identify this as a reauth flow
     * and retrieve the associated DID.
     *
     * This approach works with PAR (Pushed Authorization Request) where the
     * state parameter cannot be modified in the URL.
     */
    public function authorizeForReauth(
        string $did,
        ?string $hint = null,
        ?array $scopes = null,
        ?string $pdsEndpoint = null,
        int $ttlMinutes = 10
    ): AuthorizationRequest {
        $identifier = $hint ?? $did;
        $request = $this->authorize($identifier, $scopes, $pdsEndpoint);

        // Store keyed by original state (works with PAR)
        // The callback will look up by the state it receives
        Cache::put(
            "atp_reauth_state:{$request->state}",
            [
                'did' => $did,
                'request' => $request->toArray(),
            ],
            now()->addMinutes($ttlMinutes)
        );

        return $request;
    }

    /**
     * Handle callback for re-authentication.
     *
     * Looks up the cached reauth data by state and completes the OAuth flow.
     * Returns the access token with the associated DID.
     *
     * @throws OAuthSessionInvalidException When reauth session has expired
     * @throws AuthenticationException When callback fails
     */
    public function callbackForReauth(string $code, string $state): AccessToken
    {
        // Look up by the original state (which is what the server returns)
        $cached = Cache::pull("atp_reauth_state:{$state}");

        if (! $cached) {
            // No cached reauth data for this state - either expired or not a reauth flow
            throw OAuthSessionInvalidException::expiredRefreshToken();
        }

        $request = AuthorizationRequest::fromArray($cached['request']);

        // Complete the callback and return the token
        // The token will have the DID from the OAuth response
        return $this->callback($code, $state, $request);
    }

    /**
     * Push authorization request (PAR)
     */
    protected function pushAuthorizationRequest(
        AuthorizationServerMetadata $authServer,
        array $scopes,
        string $codeChallenge,
        string $state,
        DPoPKey $dpopKey
    ): array {
        $parUrl = $authServer->parEndpoint;

        $response = $this->dpopClient->request($authServer->issuer, $parUrl, 'POST', $dpopKey)
            ->withRequestMiddleware($this->clientAssertion->refreshAssertionMiddleware($authServer->issuer))
            ->asForm()
            ->post($parUrl, array_merge(
                $this->clientAssertion->getAuthParams($authServer->issuer),
                [
                    'redirect_uri' => $this->metadata->getRedirectUris()[0] ?? null,
                    'response_type' => 'code',
                    'scope' => implode(' ', $scopes),
                    'code_challenge' => $codeChallenge,
                    'code_challenge_method' => 'S256',
                    'state' => $state,
                ]
            ));

        if ($response->failed()) {
            throw new AuthenticationException('PAR failed: '.$response->body());
        }

        return $response->json();
    }

    /**
     * Generate PKCE code challenge (S256)
     */
    protected function generatePkceChallenge(string $verifier): string
    {
        return rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
    }
}
