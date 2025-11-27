<?php

namespace SocialDept\AtpClient\Client\Public;

use SocialDept\AtpClient\Client\Public\Requests\Atproto\IdentityPublicRequestClient;

class AtprotoPublicClient
{
    public AtpPublicClient $atp;
    public IdentityPublicRequestClient $identity;

    public function __construct(AtpPublicClient $parent)
    {
        $this->atp = $parent;
        $this->identity = new IdentityPublicRequestClient($this);
    }
}
