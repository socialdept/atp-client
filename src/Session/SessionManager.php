<?php

namespace SocialDept\AtpClient\Session;

use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use SocialDept\AtpClient\Auth\DPoPKeyManager;
use SocialDept\AtpClient\Auth\TokenRefresher;
use SocialDept\AtpClient\Contracts\CredentialProvider;
use SocialDept\AtpClient\Contracts\KeyStore;
use SocialDept\AtpClient\Data\AccessToken;
use SocialDept\AtpClient\Events\SessionAuthenticated;
use SocialDept\AtpClient\Events\SessionRefreshFailed;
use SocialDept\AtpClient\Events\SessionRefreshing;
use SocialDept\AtpClient\Events\SessionUpdated;
use SocialDept\AtpClient\Exceptions\AuthenticationException;
use SocialDept\AtpClient\Exceptions\OAuthSessionInvalidException;
use SocialDept\AtpClient\Exceptions\SessionExpiredException;
use SocialDept\AtpClient\Exceptions\TransientAuthFailureException;
use SocialDept\AtpSupport\Exceptions\HandleResolutionException;
use SocialDept\AtpSupport\Facades\Resolver;
use SocialDept\AtpSupport\Identity;
use Throwable;

class SessionManager
{
    protected array $sessions = [];

    public function __construct(
        protected CredentialProvider $credentials,
        protected TokenRefresher $refresher,
        protected DPoPKeyManager $dpopManager,
        protected KeyStore $keyStore,
        protected int $refreshThreshold = 300, // 5 minutes
        protected bool $serializeRefresh = true,
        protected int $refreshLockWait = 10,
        protected int $refreshLockTtl = 15,
    ) {
    }

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
            throw HandleResolutionException::resolutionFailed($actor);
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

        event(new SessionAuthenticated($token));

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

        // Always resolve PDS endpoint for API calls (not the auth server)
        // The auth server issuer is stored in credentials.issuer and accessed via Session::authServer()
        $pdsEndpoint = Resolver::resolvePds($creds->did);

        return new Session($creds, $dpopKey, $pdsEndpoint);
    }

    /**
     * Refresh session tokens.
     *
     * INFO: Refresh tokens are single-use. A per-DID lock serializes concurrent
     * refreshes (a publish fans out into many authed calls); the loser re-reads
     * and adopts the winner's rotated token instead of replaying a consumed one.
     *
     * @throws OAuthSessionInvalidException When refresh token is missing or refresh fails due to auth issues
     * @throws AuthenticationException When token refresh fails for other reasons
     */
    protected function refreshSession(Session $session): Session
    {
        if (! $this->serializeRefresh) {
            return $this->performRefresh($session);
        }

        $lock = Cache::lock('atp-client:refresh:'.$session->did(), $this->refreshLockTtl);

        try {
            return $lock->block($this->refreshLockWait, function () use ($session) {
                return $this->adoptRotatedSession($session) ?? $this->performRefresh($session);
            });
        } catch (LockTimeoutException) {
            // FIX: lock contention shouldn't fail the caller — refresh unsynchronized.
            return $this->performRefresh($session);
        }
    }

    /**
     * Adopt credentials a concurrent request already rotated; null if the stored
     * token still matches ours (no refresh happened) and we must refresh.
     */
    protected function adoptRotatedSession(Session $session): ?Session
    {
        $creds = $this->credentials->getCredentials($session->did());

        if (! $creds || $creds->refreshToken === $session->refreshToken()) {
            return null;
        }

        $newSession = $session->withCredentials($creds);
        $this->sessions[$session->did()] = $newSession;

        return $newSession;
    }

    /**
     * Refresh against the PDS and persist. Runs inside the per-DID lock from
     * {@see refreshSession} unless serialization is disabled.
     *
     * @throws OAuthSessionInvalidException
     * @throws AuthenticationException
     */
    protected function performRefresh(Session $session): Session
    {
        $did = $session->did();

        // Fire event before refresh (allows developers to invalidate old token)
        event(new SessionRefreshing($session));

        try {
            $newToken = $this->refresher->refresh(
                refreshToken: $session->refreshToken(),
                pdsEndpoint: $session->pdsEndpoint(),
                dpopKey: $session->dpopKey(),
                handle: $session->handle(),
                authType: $session->authType(),
            );
        } catch (Throwable $e) {
            $reason = $this->categorizeRefreshError($e);
            event(new SessionRefreshFailed($session, $e, $reason));

            throw $e;
        }

        // Update credentials (CRITICAL: refresh tokens are single-use)
        $this->credentials->updateCredentials($did, $newToken);

        // Fire event after successful refresh
        event(new SessionUpdated($session, $newToken));

        // Update session
        $newCreds = $this->credentials->getCredentials($did);
        $newSession = $session->withCredentials($newCreds);

        // Update cached session
        $this->sessions[$did] = $newSession;

        return $newSession;
    }

    /**
     * Categorize a refresh error into a reason string.
     */
    protected function categorizeRefreshError(Throwable $e): string
    {
        if ($e instanceof TransientAuthFailureException) {
            return 'transient';
        }

        if ($e instanceof OAuthSessionInvalidException) {
            if (str_contains($e->getMessage(), 'missing')) {
                return 'missing';
            }
            if (str_contains($e->getMessage(), 'expired')) {
                return 'expired';
            }

            return 'invalid';
        }

        $message = strtolower($e->getMessage());

        if (str_contains($message, 'expired') || str_contains($message, 'ExpiredToken')) {
            return 'expired';
        }

        if (str_contains($message, 'revoked') || str_contains($message, 'RevokedToken')) {
            return 'revoked';
        }

        if (str_contains($message, 'invalid') || str_contains($message, 'InvalidToken')) {
            return 'invalid';
        }

        if ($e instanceof AuthenticationException) {
            return 'auth_failed';
        }

        return 'unknown';
    }
}
