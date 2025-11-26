<?php

namespace SocialDept\AtpClient\Session;

use Illuminate\Support\Facades\Http;
use SocialDept\AtpClient\Auth\DPoPKeyManager;
use SocialDept\AtpClient\Auth\TokenRefresher;
use SocialDept\AtpClient\Contracts\CredentialProvider;
use SocialDept\AtpClient\Contracts\KeyStore;
use SocialDept\AtpClient\Data\AccessToken;
use SocialDept\AtpClient\Events\TokenRefreshed;
use SocialDept\AtpClient\Events\TokenRefreshing;
use SocialDept\AtpClient\Exceptions\AuthenticationException;
use SocialDept\AtpClient\Exceptions\HandleResolutionException;
use SocialDept\AtpClient\Exceptions\SessionExpiredException;
use SocialDept\AtpResolver\Facades\Resolver;

class SessionManager
{
    protected array $sessions = [];

    public function __construct(
        protected CredentialProvider $credentials,
        protected TokenRefresher $refresher,
        protected DPoPKeyManager $dpopManager,
        protected KeyStore $keyStore,
        protected int $refreshThreshold = 300, // 5 minutes
    ) {}

    /**
     * Resolve a handle or DID to a DID.
     *
     * @throws HandleResolutionException
     */
    protected function resolveToDid(string $handleOrDid): string
    {
        // If already a DID, return as-is
        if (str_starts_with($handleOrDid, 'did:')) {
            return $handleOrDid;
        }

        // Resolve handle to DID
        $did = Resolver::handleToDid($handleOrDid);

        if (! $did) {
            throw new HandleResolutionException($handleOrDid);
        }

        return $did;
    }

    /**
     * Get or create session for handle or DID
     */
    public function session(string $handleOrDid): Session
    {
        $did = $this->resolveToDid($handleOrDid);

        if (! isset($this->sessions[$did])) {
            $this->sessions[$did] = $this->createSession($did);
        }

        return $this->sessions[$did];
    }

    /**
     * Ensure session is valid, refresh if needed
     */
    public function ensureValid(string $handleOrDid): Session
    {
        $session = $this->session($handleOrDid);

        // Check if token needs refresh
        if ($session->expiresIn() < $this->refreshThreshold) {
            $session = $this->refreshSession($session);
        }

        return $session;
    }

    /**
     * Create session from app password
     */
    public function fromAppPassword(
        string $handleOrDid,
        string $password
    ): Session {
        $did = $this->resolveToDid($handleOrDid);
        $pdsEndpoint = Resolver::resolvePds($did);

        $response = Http::post($pdsEndpoint.'/xrpc/com.atproto.server.createSession', [
            'identifier' => $handleOrDid,
            'password' => $password,
        ]);

        if ($response->failed()) {
            throw new AuthenticationException('Login failed');
        }

        $token = AccessToken::fromResponse($response->json(), $handleOrDid, $pdsEndpoint);

        // Store credentials using DID as key
        $this->credentials->storeCredentials($did, $token);

        return $this->createSession($did);
    }

    /**
     * Create session from credentials
     */
    protected function createSession(string $did): Session
    {
        $creds = $this->credentials->getCredentials($did);

        if (! $creds) {
            throw new SessionExpiredException("No credentials found for {$did}");
        }

        // Get or create DPoP key
        $sessionId = 'session_'.hash('sha256', $creds->did);
        $dpopKey = $this->keyStore->get($sessionId);

        if (! $dpopKey) {
            $dpopKey = $this->dpopManager->generateKey($sessionId);
        }

        // Use stored issuer if available, otherwise resolve PDS endpoint
        $pdsEndpoint = $creds->issuer ?? Resolver::resolvePds($creds->did);

        return new Session($creds, $dpopKey, $pdsEndpoint);
    }

    /**
     * Refresh session tokens
     */
    protected function refreshSession(Session $session): Session
    {
        $did = $session->did();

        // Fire event before refresh (allows developers to invalidate old token)
        event(new TokenRefreshing($did, $session->refreshToken()));

        $newToken = $this->refresher->refresh(
            refreshToken: $session->refreshToken(),
            pdsEndpoint: $session->pdsEndpoint(),
            dpopKey: $session->dpopKey(),
            handle: $session->handle(),
        );

        // Update credentials (CRITICAL: refresh tokens are single-use)
        $this->credentials->updateCredentials($did, $newToken);

        // Fire event after successful refresh
        event(new TokenRefreshed($did, $newToken));

        // Update session
        $newCreds = $this->credentials->getCredentials($did);
        $newSession = $session->withCredentials($newCreds);

        // Update cached session
        $this->sessions[$did] = $newSession;

        return $newSession;
    }
}
