<?php

namespace SocialDept\AtpClient;

use Illuminate\Http\Client\Factory;
use SocialDept\AtpClient\Client\Client;
use SocialDept\AtpClient\Client\AtprotoClient;
use SocialDept\AtpClient\Client\BskyClient;
use SocialDept\AtpClient\Client\ChatClient;
use SocialDept\AtpClient\Client\OzoneClient;
use SocialDept\AtpClient\Session\SessionManager;

class AtpClient
{
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
        SessionManager $sessions,
        Factory $http,
        string $identifier,
    ) {
        // Load the network client
        $this->client = new Client($this, $sessions, $http, $identifier);

        // Load all function collections
        $this->bsky = new BskyClient($this);
        $this->atproto = new AtprotoClient($this);
        $this->chat = new ChatClient($this);
        $this->ozone = new OzoneClient($this);
    }
}
