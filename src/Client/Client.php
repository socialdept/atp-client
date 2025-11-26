<?php

namespace SocialDept\AtpClient\Client;

use Illuminate\Http\Client\Factory;
use SocialDept\AtpClient\AtpClient;
use SocialDept\AtpClient\Auth\DPoPNonceManager;
use SocialDept\AtpClient\Http\HasHttp;
use SocialDept\AtpClient\Session\SessionManager;

class Client
{
    use HasHttp;

    /**
     * The parent AtpClient instance we belong to
     */
    public AtpClient $atp;

    public function __construct(
        AtpClient $parent,
        SessionManager $sessions,
        Factory $http,
        string $identifier,
    ) {
        $this->atp = $parent;
        $this->sessions = $sessions;
        $this->http = $http;
        $this->identifier = $identifier;
        $this->nonceManager = app(DPoPNonceManager::class);
    }
}
