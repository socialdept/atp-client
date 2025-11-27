<?php

namespace SocialDept\AtpClient\Session;

use Illuminate\Support\Facades\Http;
use SocialDept\AtpClient\Auth\DPoPKeyManager;
use SocialDept\AtpClient\Auth\TokenRefresher;
use SocialDept\AtpClient\Contracts\CredentialProvider;
use SocialDept\AtpClient\Contracts\KeyStore;
use SocialDept\AtpClient\Data\AccessToken;
use SocialDept\AtpClient\Events\LegacyUserAuthenticated;
use SocialDept\AtpClient\Events\TokenRefreshed;
use SocialDept\AtpClient\Events\TokenRefreshing;
use SocialDept\AtpClient\Exceptions\AuthenticationException;
use SocialDept\AtpClient\Exceptions\HandleResolutionException;
use SocialDept\AtpClient\Exceptions\SessionExpiredException;
use SocialDept\AtpResolver\Facades\Resolver;
use SocialDept\AtpResolver\Support\Identity;

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
     * Resolve an actor (handle or DID) to a DID.
     *
     * @throws HandleResolutionException
     */
    protected function resolveToDid(string $actor): string
    {
        // If already a DID, return as-is
        if (Identity::isDid($actor)) {
            return $actor;
        }

        // Resolve handle to DID
        $did = Resolver::handleToDid($actor);

        if (! $did) {
            throw new HandleResolutionException($actor);
        }

        return $did;
    }

    /**
     * Get or create session for an actor.
     */
    public function session(string $actor): Session
    {
        $did = $this->resolveToDid($actor);

        if (! isset($this->sessions[$did])) {
            $this->sessions[$did] = $this->createSession($did);
        }

        return $this->sessions[$did];
    }

    /**
     * Ensure session is valid, refresh if needed.
     */
    public function ensureValid(string $actor): Session
    {
        $session = $this->session($actor);

        // Check if token needs refresh
        if ($session->expiresIn() < $this->refreshThreshold) {
            $session = $this->refreshSession($session);
        }

        return $session;
    }

    /**
     * Create session from app password.
     */
    public function fromAppPassword(
        string $actor,
        string $password
    ): Session {
        $did = $this->resolveToDid($actor);
        $pdsEndpoint = Resolver::resolvePds($did);

        $response = Http::post($pdsEndpoint.'/xrpc/com.atproto.server.createSession', [
            'identifier' => $actor,
            'password' => $password,
        ]);

        if ($response->failed()) {
            throw new AuthenticationException('Login failed');
        }

        $token = AccessToken::fromResponse($response->json(), $actor, $pdsEndpoint);

        // Store credentials using DID as key
        $this->credentials->storeCredentials($did, $token);

        event(new LegacyUserAuthenticated($token));

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
        event(new TokenRefreshing($session));

        $newToken = $this->refresher->refresh(
            refreshToken: $session->refreshToken(),
            pdsEndpoint: $session->pdsEndpoint(),
            dpopKey: $session->dpopKey(),
            handle: $session->handle(),
            authType: $session->authType(),
        );

        // Update credentials (CRITICAL: refresh tokens are single-use)
        $this->credentials->updateCredentials($did, $newToken);

        // Fire event after successful refresh
        event(new TokenRefreshed($session, $newToken));

        // Update session
        $newCreds = $this->credentials->getCredentials($did);
        $newSession = $session->withCredentials($newCreds);

        // Update cached session
        $this->sessions[$did] = $newSession;

        return $newSession;
    }
}
