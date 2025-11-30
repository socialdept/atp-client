<?php

namespace SocialDept\AtpClient;

use SocialDept\AtpClient\Client\AtprotoClient;
use SocialDept\AtpClient\Client\BskyClient;
use SocialDept\AtpClient\Client\ChatClient;
use SocialDept\AtpClient\Client\Client;
use SocialDept\AtpClient\Client\OzoneClient;
use SocialDept\AtpClient\Concerns\HasExtensions;
use SocialDept\AtpClient\Session\SessionManager;

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
}
