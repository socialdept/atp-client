<?php

namespace SocialDept\AtpClient\Client;

use SocialDept\AtpClient\AtpClient;
use SocialDept\AtpClient\Client\Requests\Atproto;

class AtprotoClient
{
    /**
     * The parent AtpClient instance
     */
    public AtpClient $atp;

    /**
     * Repository operations (com.atproto.repo.*)
     */
    public Atproto\RepoRequestClient $repo;

    /**
     * Server operations (com.atproto.server.*)
     */
    public Atproto\ServerRequestClient $server;

    /**
     * Identity operations (com.atproto.identity.*)
     */
    public Atproto\IdentityRequestClient $identity;

    /**
     * Sync operations (com.atproto.sync.*)
     */
    public Atproto\SyncRequestClient $sync;

    public function __construct(AtpClient $parent)
    {
        $this->atp = $parent;
        $this->repo = new Atproto\RepoRequestClient($this);
        $this->server = new Atproto\ServerRequestClient($this);
        $this->identity = new Atproto\IdentityRequestClient($this);
        $this->sync = new Atproto\SyncRequestClient($this);
    }
}
