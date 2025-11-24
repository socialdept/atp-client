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
    public Atproto\Repo $repo;

    /**
     * Server operations (com.atproto.server.*)
     */
    public Atproto\Server $server;

    /**
     * Identity operations (com.atproto.identity.*)
     */
    public Atproto\Identity $identity;

    /**
     * Sync operations (com.atproto.sync.*)
     */
    public Atproto\Sync $sync;

    public function __construct(AtpClient $parent)
    {
        $this->atp = $parent;
        $this->repo = new Atproto\Repo($this);
        $this->server = new Atproto\Server($this);
        $this->identity = new Atproto\Identity($this);
        $this->sync = new Atproto\Sync($this);
    }
}
