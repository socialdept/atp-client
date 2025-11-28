<?php

namespace SocialDept\AtpClient\Client;

use SocialDept\AtpClient\AtpClient;
use SocialDept\AtpClient\Client\Requests\Chat;
use SocialDept\AtpClient\Concerns\HasDomainExtensions;

class ChatClient
{
    use HasDomainExtensions;
    /**
     * The parent AtpClient instance
     */
    protected AtpClient $atp;

    /**
     * Conversation operations (chat.bsky.convo.*)
     */
    public Chat\ConvoRequestClient $convo;

    /**
     * Actor operations (chat.bsky.actor.*)
     */
    public Chat\ActorRequestClient $actor;

    public function __construct(AtpClient $parent)
    {
        $this->atp = $parent;
        $this->convo = new Chat\ConvoRequestClient($this);
        $this->actor = new Chat\ActorRequestClient($this);
    }

    protected function getDomainName(): string
    {
        return 'chat';
    }

    protected function getRootClientClass(): string
    {
        return AtpClient::class;
    }

    public function root(): AtpClient
    {
        return $this->atp;
    }
}
