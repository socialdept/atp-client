<?php

namespace SocialDept\AtpClient\Client\Public;

use SocialDept\AtpClient\Client\Public\Requests\Atproto\IdentityPublicRequestClient;
use SocialDept\AtpClient\Client\Public\Requests\Atproto\RepoPublicRequestClient;
use SocialDept\AtpClient\Concerns\HasDomainExtensions;

class AtprotoPublicClient
{
    use HasDomainExtensions;

    public AtpPublicClient $atp;
    public IdentityPublicRequestClient $identity;
    public RepoPublicRequestClient $repo;

    public function __construct(AtpPublicClient $parent)
    {
        $this->atp = $parent;
        $this->identity = new IdentityPublicRequestClient($this);
        $this->repo = new RepoPublicRequestClient($this);
    }

    protected function getDomainName(): string
    {
        return 'atproto';
    }

    protected function getRootClientClass(): string
    {
        return AtpPublicClient::class;
    }
}
