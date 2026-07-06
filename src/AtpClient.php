<?php

namespace SocialDept\AtpClient;

use SocialDept\AtpClient\Auth\OAuthErrorClassifier;
use SocialDept\AtpClient\Client\AtprotoClient;
use SocialDept\AtpClient\Client\BskyClient;
use SocialDept\AtpClient\Client\ChatClient;
use SocialDept\AtpClient\Client\Client;
use SocialDept\AtpClient\Client\OzoneClient;
use SocialDept\AtpClient\Concerns\HasExtensions;
use SocialDept\AtpClient\Data\SessionHealth;
use SocialDept\AtpClient\Enums\RefreshFailureReason;
use SocialDept\AtpClient\Exceptions\AtpResponseException;
use SocialDept\AtpClient\Exceptions\AuthenticationException;
use SocialDept\AtpClient\Session\SessionManager;
use Throwable;

class AtpClient
{
    use HasExtensions;

    /**
     * Raw API communication/networking class
     */
    public Client $client;

    /**
     * Collection of Bluesky (app.bsky.*) related functions
     */
    public BskyClient $bsky;

    /**
     * Collection of AT Protocol (com.atproto.*) related functions
     */
    public AtprotoClient $atproto;

    /**
     * Collection of Chat (chat.bsky.*) related functions
     */
    public ChatClient $chat;

    /**
     * Collection of Ozone (tools.ozone.*) related functions
     */
    public OzoneClient $ozone;

    public function __construct(
        ?SessionManager $sessions = null,
        ?string $did = null,
        ?string $serviceUrl = null,
    ) {
        // Load the network client (supports both public and authenticated modes)
        $this->client = new Client($this, $sessions, $did, $serviceUrl);

        // Load all function collections
        $this->bsky = new BskyClient($this);
        $this->atproto = new AtprotoClient($this);
        $this->chat = new ChatClient($this);
        $this->ozone = new OzoneClient($this);
    }

    /**
     * Check if client is in public mode (no authentication).
     */
    public function isPublicMode(): bool
    {
        return $this->client->isPublicMode();
    }

    /**
     * Cheap authenticated liveness probe. Calls `com.atproto.server.getSession`
     * (which refreshes the access token under the hood if it is near expiry) and
     * maps the outcome to a {@see SessionHealth}: healthy, terminal (dead grant /
     * account gone or inactive), stale-access, or transient (`!reachable`).
     *
     * The keep-alive reconciler uses this to decide whether to flag a session for
     * reconnect (only on a real refusal) vs leave it alone (transient).
     */
    public function probe(): SessionHealth
    {
        try {
            $session = $this->atproto->server->getSession();
        } catch (AuthenticationException $e) {
            // ensureValid attempted a refresh and it failed — the attached reason
            // (or a classification of it) decides terminal vs transient.
            $reason = $e->reason ?? (new OAuthErrorClassifier())->classifyThrowable($e);

            return $reason->isTerminal()
                ? SessionHealth::terminal($reason)
                : SessionHealth::unreachable($reason);
        } catch (AtpResponseException $e) {
            return $this->healthFromResponseException($e);
        } catch (Throwable) {
            // Connection failure / anything else — treat as transient.
            return SessionHealth::unreachable(RefreshFailureReason::Network);
        }

        if ($session->active === false) {
            return SessionHealth::inactive($session->status);
        }

        return SessionHealth::healthy();
    }

    /**
     * Map a failed getSession resource response to session health. A 401 on the
     * resource call means the ACCESS token is stale (refresh may still work), NOT
     * that the grant is dead — so it is stale-access, never terminal.
     */
    private function healthFromResponseException(AtpResponseException $e): SessionHealth
    {
        $status = $e->httpStatus;
        $code = strtolower($e->errorCode);
        $message = strtolower($e->errorMessage);

        // Infrastructure transient — never flag.
        if ($status === 0 || $status === 429 || $status >= 500) {
            return SessionHealth::unreachable();
        }

        // Account gone / inactive — terminal.
        if (str_contains($message, 'could not find user') || str_contains($message, 'account not found')) {
            return SessionHealth::terminal(RefreshFailureReason::AccountNotFound);
        }
        if (str_contains($code, 'takedown') || str_contains($code, 'deactivated') || str_contains($code, 'suspended')) {
            return SessionHealth::inactive($e->errorCode);
        }

        // Stale/invalid access token on a resource call — refreshable, not terminal.
        if ($status === 401 || str_contains($code, 'token') || str_contains($code, 'expired') || str_contains($code, 'authrequired')) {
            return SessionHealth::staleAccess();
        }

        // use_dpop_nonce and other unexpected 4xx — transient.
        return SessionHealth::unreachable();
    }
}
