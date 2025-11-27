<?php

namespace SocialDept\AtpClient\Client\Public\Requests;

use SocialDept\AtpClient\Client\Public\AtpPublicClient;

class PublicRequest
{
    public AtpPublicClient $atp;

    public function __construct($parent)
    {
        $this->atp = $parent->atp;
    }
}
