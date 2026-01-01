<?php

namespace SocialDept\AtpClient\Auth;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use SocialDept\AtpClient\Contracts\KeyStore;
use SocialDept\AtpClient\Data\AccessToken;
use SocialDept\AtpClient\Data\AuthorizationRequest;
use SocialDept\AtpClient\Data\DPoPKey;
use SocialDept\AtpClient\Events\SessionAuthenticated;
use SocialDept\AtpClient\Exceptions\AuthenticationException;
use SocialDept\AtpClient\Exceptions\OAuthSessionInvalidException;
use SocialDept\AtpClient\Http\DPoPClient;
use SocialDept\AtpResolver\Facades\Resolver;

class OAuthEngine
{
    public function __construct(
        protected DPoPKeyManager $dpopManager,
        protected ClientMetadataManager $metadata,
        protected DPoPClient $dpopClient,
        protected ClientAssertionManager $clientAssertion,
        protected KeyStore $keyStore,
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

        // Generate PKCE challenge
        $codeVerifier = Str::random(128);
        $codeChallenge = $this->generatePkceChallenge($codeVerifier);

        // Generate state
        $state = Str::random(32);

        // Generate DPoP key for this flow
        $dpopKey = $this->dpopManager->generateKey('oauth_'.$state);

        // Build PAR request
        $parResponse = $this->pushAuthorizationRequest(
            $pdsEndpoint,
            $scopes,
            $codeChallenge,
            $state,
            $dpopKey
        );

        // Build authorization URL
        $authUrl = $pdsEndpoint.'/oauth/authorize?'.http_build_query([
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

        $tokenUrl = $request->pdsEndpoint.'/oauth/token';

        $response = $this->dpopClient->request($request->pdsEndpoint, $tokenUrl, 'POST', $request->dpopKey)
            ->asForm()
            ->post($tokenUrl, array_merge(
                $this->clientAssertion->getAuthParams($request->pdsEndpoint),
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

        $token = AccessToken::fromResponse($response->json(), $request->handle, $request->pdsEndpoint);

        // Store the DPoP key with the session ID so future requests can use it
        // The token is bound to this key's thumbprint (cnf.jkt claim)
        $sessionId = 'session_'.hash('sha256', $token->did);
        $this->keyStore->store($sessionId, $request->dpopKey);

        event(new SessionAuthenticated($token));

        return $token;
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
        string $pdsEndpoint,
        array $scopes,
        string $codeChallenge,
        string $state,
        DPoPKey $dpopKey
    ): array {
        $parUrl = $pdsEndpoint.'/oauth/par';

        $response = $this->dpopClient->request($pdsEndpoint, $parUrl, 'POST', $dpopKey)
            ->asForm()
            ->post($parUrl, array_merge(
                $this->clientAssertion->getAuthParams($pdsEndpoint),
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
