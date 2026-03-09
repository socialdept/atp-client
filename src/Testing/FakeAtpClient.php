<?php

namespace SocialDept\AtpClient\Testing;

use SocialDept\AtpClient\AtpClient;
use SocialDept\AtpClient\Client\AtprotoClient;
use SocialDept\AtpClient\Client\BskyClient;
use SocialDept\AtpClient\Client\ChatClient;
use SocialDept\AtpClient\Client\OzoneClient;

class FakeAtpClient extends AtpClient
{
    public function __construct(
        array $stubs = [],
        ?string $did = null,
        ?string $serviceUrl = null,
    ) {
        // Don't call parent — we wire everything manually
        $this->client = new FakeClient($this, $stubs, $serviceUrl);

        // Set DID on the underlying client for authenticated mode detection
        if ($did !== null) {
            $this->fakeClient()->setFakeDid($did);
        }

        // Load all function collections exactly like the real client
        $this->bsky = new BskyClient($this);
        $this->atproto = new AtprotoClient($this);
        $this->chat = new ChatClient($this);
        $this->ozone = new OzoneClient($this);
    }

    /**
     * Get the underlying FakeClient (type-safe accessor).
     */
    public function fakeClient(): FakeClient
    {
        return $this->client;
    }

    /**
     * Check if client is in public mode.
     */
    public function isPublicMode(): bool
    {
        return $this->client->isPublicMode();
    }
}
