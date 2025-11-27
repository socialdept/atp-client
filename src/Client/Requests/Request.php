<?php

namespace SocialDept\AtpClient\Client\Requests;

use SocialDept\AtpClient\AtpClient;

class Request
{
    /**
     * The parent AtpClient instance we belong to
     */
    protected AtpClient $atp;

    public function __construct($parent)
    {
        $this->atp = $parent->atp;
    }
}
