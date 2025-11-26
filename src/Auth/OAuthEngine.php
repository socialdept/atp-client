<?php

namespace SocialDept\AtpClient\Auth;

use Illuminate\Http\Client\Factory as HttpClient;
use Illuminate\Support\Str;
use SocialDept\AtpClient\Data\AccessToken;
use SocialDept\AtpClient\Data\AuthorizationRequest;
use SocialDept\AtpClient\Exceptions\AuthenticationException;
use SocialDept\Resolver\Facades\Resolver;

class OAuthEngine
{
    public function __construct(
        protected HttpClient $http,
        protected DPoPKeyManager $dpopManager,
        protected ClientMetadataManager $metadata,
        protected DPoPNonceManager $nonceManager,
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
        $tokenData = [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->metadata->getRedirectUris()[0] ?? null,
            'client_id' => $this->metadata->getClientId(),
            'code_verifier' => $request->codeVerifier,
        ];

        // Get cached nonce
        $nonce = $this->nonceManager->getNonce($pdsEndpoint);

        $dpopProof = $this->dpopManager->createProof(
            key: $request->dpopKey,
            method: 'POST',
            url: $tokenUrl,
            nonce: $nonce,
        );

        $response = $this->http
            ->withHeaders([
                'DPoP' => $dpopProof,
                'Content-Type' => 'application/x-www-form-urlencoded',
            ])
            ->asForm()
            ->post($tokenUrl, $tokenData);

        // Handle use_dpop_nonce error - retry with server-provided nonce
        if ($response->status() === 400) {
            $error = $response->json('error');

            if ($error === 'use_dpop_nonce' && $response->header('DPoP-Nonce')) {
                $nonce = $response->header('DPoP-Nonce');
                $this->nonceManager->storeNonce($pdsEndpoint, $nonce);

                // Retry with new nonce
                $dpopProof = $this->dpopManager->createProof(
                    key: $request->dpopKey,
                    method: 'POST',
                    url: $tokenUrl,
                    nonce: $nonce,
                );

                $response = $this->http
                    ->withHeaders([
                        'DPoP' => $dpopProof,
                        'Content-Type' => 'application/x-www-form-urlencoded',
                    ])
                    ->asForm()
                    ->post($tokenUrl, $tokenData);
            }
        }

        // Store nonce from response for future requests
        if ($response->header('DPoP-Nonce')) {
            $this->nonceManager->storeNonce($pdsEndpoint, $response->header('DPoP-Nonce'));
        }

        if ($response->failed()) {
            throw new AuthenticationException(
                'Token exchange failed: '.$response->body()
            );
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
        $dpopKey
    ): array {
        $parUrl = $pdsEndpoint.'/oauth/par';
        $parData = [
            'client_id' => $this->metadata->getClientId(),
            'redirect_uri' => $this->metadata->getRedirectUris()[0] ?? null,
            'response_type' => 'code',
            'scope' => implode(' ', $scopes),
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
            'state' => Str::random(32),
        ];

        // Try with cached nonce first (may be empty on first request)
        $nonce = $this->nonceManager->getNonce($pdsEndpoint);

        $dpopProof = $this->dpopManager->createProof(
            key: $dpopKey,
            method: 'POST',
            url: $parUrl,
            nonce: $nonce,
        );

        $response = $this->http
            ->withHeaders(['DPoP' => $dpopProof])
            ->asForm()
            ->post($parUrl, $parData);

        // Handle use_dpop_nonce error - retry with server-provided nonce
        if ($response->status() === 400) {
            $error = $response->json('error');

            if ($error === 'use_dpop_nonce' && $response->header('DPoP-Nonce')) {
                $nonce = $response->header('DPoP-Nonce');
                $this->nonceManager->storeNonce($pdsEndpoint, $nonce);

                // Retry with new nonce
                $dpopProof = $this->dpopManager->createProof(
                    key: $dpopKey,
                    method: 'POST',
                    url: $parUrl,
                    nonce: $nonce,
                );

                $response = $this->http
                    ->withHeaders(['DPoP' => $dpopProof])
                    ->asForm()
                    ->post($parUrl, $parData);
            }
        }

        // Store nonce from successful response for future requests
        if ($response->header('DPoP-Nonce')) {
            $this->nonceManager->storeNonce($pdsEndpoint, $response->header('DPoP-Nonce'));
        }

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
        // Parse the request URI to extract the base PDS endpoint
        $parts = parse_url($requestUri);

        return ($parts['scheme'] ?? 'https').'://'.($parts['host'] ?? '');
    }
}
