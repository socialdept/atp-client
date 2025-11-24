<?php

namespace SocialDept\AtpClient\Client;

use SocialDept\AtpClient\AtpClient;
use SocialDept\AtpClient\Client\Requests\Chat;

class ChatClient
{
    /**
     * The parent AtpClient instance
     */
    public AtpClient $atp;

    /**
     * Conversation operations (chat.bsky.convo.*)
     */
    public Chat\Convo $convo;

    /**
     * Actor operations (chat.bsky.actor.*)
     */
    public Chat\Actor $actor;

    public function __construct(AtpClient $parent)
    {
        $this->atp = $parent;
        $this->convo = new Chat\Convo($this);
        $this->actor = new Chat\Actor($this);
    }
}
