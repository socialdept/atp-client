<?php

namespace SocialDept\AtpClient\Client;

use SocialDept\AtpClient\AtpClient;
use SocialDept\AtpClient\Http\DPoPClient;
use SocialDept\AtpClient\Http\HasHttp;
use SocialDept\AtpClient\Session\Session;
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
        string $did,
    ) {
        $this->atp = $parent;
        $this->sessions = $sessions;
        $this->did = $did;
        $this->dpopClient = app(DPoPClient::class);
    }

    /**
     * Get the current session.
     */
    public function session(): Session
    {
        return $this->sessions->session($this->did);
    }
}
