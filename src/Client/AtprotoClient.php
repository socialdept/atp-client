<?php

namespace SocialDept\AtpClient\Client;

use SocialDept\AtpClient\AtpClient;
use SocialDept\AtpClient\Client\Requests\Atproto;
use SocialDept\AtpClient\Concerns\HasDomainExtensions;

class AtprotoClient
{
    use HasDomainExtensions;
    /**
     * The parent AtpClient instance
     */
    protected AtpClient $atp;

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

    protected function getDomainName(): string
    {
        return 'atproto';
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
