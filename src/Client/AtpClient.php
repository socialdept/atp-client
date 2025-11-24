<?php

namespace SocialDept\AtpClient\Client;

use Illuminate\Http\Client\Factory;
use SocialDept\AtpClient\Auth\DPoPNonceManager;
use SocialDept\AtpClient\Session\SessionManager;

class AtpClient
{
    public function __construct(
        protected SessionManager $sessions,
        protected Factory $http,
        protected string $identifier,
    ) {}

    /**
     * Get Bluesky client (app.bsky.*)
     */
    public function bsky(): BskyClient
    {
        return new BskyClient($this->sessions, $this->http, $this->identifier);
    }

    /**
     * Get AT Protocol client (com.atproto.*)
     */
    public function atproto(): AtprotoClient
    {
        return new AtprotoClient($this->sessions, $this->http, $this->identifier);
    }

    /**
     * Get Chat client (chat.bsky.*)
     */
    public function chat(): ChatClient
    {
        return new ChatClient($this->sessions, $this->http, $this->identifier);
    }

    /**
     * Get Ozone client (tools.ozone.*)
     */
    public function ozone(): OzoneClient
    {
        return new OzoneClient($this->sessions, $this->http, $this->identifier);
    }

    /**
     * Get the current session identifier
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * Get the session manager
     */
    public function getSessionManager(): SessionManager
    {
        return $this->sessions;
    }
}
