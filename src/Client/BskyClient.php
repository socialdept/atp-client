<?php

namespace SocialDept\AtpClient\Client;

use SocialDept\AtpClient\AtpClient;
use SocialDept\AtpClient\Client\Requests\Bsky;

class BskyClient
{
    /**
     * The parent AtpClient instance
     */
    public AtpClient $atp;

    /**
     * Feed operations (app.bsky.feed.*)
     */
    public Bsky\Feed $feed;

    /**
     * Actor operations (app.bsky.actor.*)
     */
    public Bsky\Actor $actor;

    public function __construct(AtpClient $parent)
    {
        $this->atp = $parent;
        $this->feed = new Bsky\Feed($this);
        $this->actor = new Bsky\Actor($this);
    }
}
