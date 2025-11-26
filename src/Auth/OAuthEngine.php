<?php

namespace SocialDept\AtpClient\Auth;

use Illuminate\Support\Str;
use SocialDept\AtpClient\Data\AccessToken;
use SocialDept\AtpClient\Data\AuthorizationRequest;
use SocialDept\AtpClient\Data\DPoPKey;
use SocialDept\AtpClient\Exceptions\AuthenticationException;
use SocialDept\AtpClient\Http\DPoPClient;
use SocialDept\AtpResolver\Facades\Resolver;

class OAuthEngine
{
    public function __construct(
        protected DPoPKeyManager $dpopManager,
        protected ClientMetadataManager $metadata,
        protected DPoPClient $dpopClient,
    ) {}

    /**
     * Initiate OAuth flow
     */
    public function authorize(
        string $identifier,
        array $scopes = ['atproto', 'transition:generic'],
        ?string $pdsEndpoint = null
    ): AuthorizationRequest {
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

        // Get PDS endpoint from request
        $pdsEndpoint = $this->extractPdsFromRequestUri($request->requestUri);
        $tokenUrl = $pdsEndpoint.'/oauth/token';

        $response = $this->dpopClient->request($pdsEndpoint, $tokenUrl, 'POST', $request->dpopKey)
            ->asForm()
            ->post($tokenUrl, [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => $this->metadata->getRedirectUris()[0] ?? null,
                'client_id' => $this->metadata->getClientId(),
                'code_verifier' => $request->codeVerifier,
            ]);

        if ($response->failed()) {
            throw new AuthenticationException('Token exchange failed: '.$response->body());
        }

        return AccessToken::fromResponse($response->json());
    }

    /**
     * Push authorization request (PAR)
     */
    protected function pushAuthorizationRequest(
        string $pdsEndpoint,
        array $scopes,
        string $codeChallenge,
        DPoPKey $dpopKey
    ): array {
        $parUrl = $pdsEndpoint.'/oauth/par';

        $response = $this->dpopClient->request($pdsEndpoint, $parUrl, 'POST', $dpopKey)
            ->asForm()
            ->post($parUrl, [
                'client_id' => $this->metadata->getClientId(),
                'redirect_uri' => $this->metadata->getRedirectUris()[0] ?? null,
                'response_type' => 'code',
                'scope' => implode(' ', $scopes),
                'code_challenge' => $codeChallenge,
                'code_challenge_method' => 'S256',
                'state' => Str::random(32),
            ]);

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

    /**
     * Extract PDS endpoint from request URI
     */
    protected function extractPdsFromRequestUri(string $requestUri): string
    {
        $parts = parse_url($requestUri);

        return ($parts['scheme'] ?? 'https').'://'.($parts['host'] ?? '');
    }
}
