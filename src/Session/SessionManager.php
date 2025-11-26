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
use SocialDept\AtpClient\Exceptions\SessionExpiredException;
use SocialDept\Resolver\Facades\Resolver;

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
     * Get or create session for identifier
     */
    public function session(string $identifier): Session
    {
        if (! isset($this->sessions[$identifier])) {
            $this->sessions[$identifier] = $this->createSession($identifier);
        }

        return $this->sessions[$identifier];
    }

    /**
     * Ensure session is valid, refresh if needed
     */
    public function ensureValid(string $identifier): Session
    {
        $session = $this->session($identifier);

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
        string $identifier,
        string $password
    ): Session {
        $pdsEndpoint = Resolver::resolvePds($identifier);

        $response = Http::post($pdsEndpoint.'/xrpc/com.atproto.server.createSession', [
            'identifier' => $identifier,
            'password' => $password,
        ]);

        if ($response->failed()) {
            throw new AuthenticationException('Login failed');
        }

        $token = AccessToken::fromResponse($response->json());

        // Store credentials
        $this->credentials->storeCredentials($identifier, $token);

        return $this->createSession($identifier);
    }

    /**
     * Create session from credentials
     */
    protected function createSession(string $identifier): Session
    {
        $creds = $this->credentials->getCredentials($identifier);

        if (! $creds) {
            throw new SessionExpiredException("No credentials found for {$identifier}");
        }

        // Get or create DPoP key
        $sessionId = 'session_'.hash('sha256', $creds->did);
        $dpopKey = $this->keyStore->get($sessionId);

        if (! $dpopKey) {
            $dpopKey = $this->dpopManager->generateKey($sessionId);
        }

        // Resolve PDS endpoint
        $pdsEndpoint = Resolver::resolvePds($creds->did);

        return new Session($creds, $dpopKey, $pdsEndpoint);
    }

    /**
     * Refresh session tokens
     */
    protected function refreshSession(Session $session): Session
    {
        // Fire event before refresh (allows developers to invalidate old token)
        event(new TokenRefreshing($session->identifier(), $session->refreshToken()));

        $newToken = $this->refresher->refresh(
            refreshToken: $session->refreshToken(),
            pdsEndpoint: $session->pdsEndpoint(),
            dpopKey: $session->dpopKey(),
        );

        // Update credentials (CRITICAL: refresh tokens are single-use)
        $this->credentials->updateCredentials(
            $session->identifier(),
            $newToken
        );

        // Fire event after successful refresh
        event(new TokenRefreshed($session->identifier(), $newToken));

        // Update session
        $newCreds = $this->credentials->getCredentials($session->identifier());
        $newSession = $session->withCredentials($newCreds);

        // Update cached session
        $this->sessions[$session->identifier()] = $newSession;

        return $newSession;
    }
}
