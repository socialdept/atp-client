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
     * Initiate re-authentication for an existing user.
     *
     * Unlike authorize(), this stores the request keyed by DID and embeds
     * the DID in the state parameter for retrieval during callback.
     * This is useful for popup-based reauth flows where session state
     * cannot be shared between windows.
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

        // Create a new request with the DID embedded in the state
        $reauthState = $this->encodeReauthState($did, $request->state);

        $reauthRequest = new AuthorizationRequest(
            url: str_replace(
                'state='.$request->state,
                'state='.$reauthState,
                $request->url
            ),
            state: $reauthState,
            codeVerifier: $request->codeVerifier,
            dpopKey: $request->dpopKey,
            requestUri: $request->requestUri,
            pdsEndpoint: $request->pdsEndpoint,
            handle: $request->handle,
        );

        // Store keyed by DID, not session
        Cache::put(
            "atp_reauth:{$did}",
            $reauthRequest->toArray(),
            now()->addMinutes($ttlMinutes)
        );

        return $reauthRequest;
    }

    /**
     * Handle callback for re-authentication.
     *
     * Extracts DID from state and validates against cached request.
     */
    public function callbackForReauth(string $code, string $state): AccessToken
    {
        $decoded = $this->decodeReauthState($state);

        if (! $decoded) {
            throw new AuthenticationException('Invalid reauth state: could not decode');
        }

        $did = $decoded['did'];
        $originalState = $decoded['state'];

        $cached = Cache::pull("atp_reauth:{$did}");

        if (! $cached) {
            throw OAuthSessionInvalidException::expiredRefreshToken();
        }

        $request = AuthorizationRequest::fromArray($cached);

        // Validate the original state matches
        if ($originalState !== $this->extractOriginalState($request->state)) {
            throw new AuthenticationException('State mismatch in reauth callback');
        }

        // Complete the callback using the original request's data
        // but with a reconstructed request that has the original state
        $originalRequest = new AuthorizationRequest(
            url: $request->url,
            state: $originalState,
            codeVerifier: $request->codeVerifier,
            dpopKey: $request->dpopKey,
            requestUri: $request->requestUri,
            pdsEndpoint: $request->pdsEndpoint,
            handle: $request->handle,
        );

        return $this->callback($code, $originalState, $originalRequest);
    }

    /**
     * Encode DID into state for reauth flow.
     */
    protected function encodeReauthState(string $did, string $originalState): string
    {
        return rtrim(strtr(base64_encode(json_encode([
            'did' => $did,
            'state' => $originalState,
        ])), '+/', '-_'), '=');
    }

    /**
     * Decode reauth state to extract DID and original state.
     *
     * @return array{did: string, state: string}|null
     */
    protected function decodeReauthState(string $state): ?array
    {
        try {
            $padded = str_pad(strtr($state, '-_', '+/'), strlen($state) % 4, '=');
            $decoded = json_decode(base64_decode($padded), true);

            if (! isset($decoded['did'], $decoded['state'])) {
                return null;
            }

            return $decoded;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Extract the original state from a potentially encoded reauth state.
     */
    protected function extractOriginalState(string $state): string
    {
        $decoded = $this->decodeReauthState($state);

        return $decoded['state'] ?? $state;
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
