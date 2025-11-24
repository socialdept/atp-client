<?php

namespace SocialDept\AtpClient\Client;

use SocialDept\AtpClient\AtpClient;
use SocialDept\AtpClient\Client\Requests\Ozone;

class OzoneClient
{
    /**
     * The parent AtpClient instance
     */
    public AtpClient $atp;

    /**
     * Moderation operations (tools.ozone.moderation.*)
     */
    public Ozone\Moderation $moderation;

    /**
     * Server operations (tools.ozone.server.*)
     */
    public Ozone\Server $server;

    /**
     * Team operations (tools.ozone.team.*)
     */
    public Ozone\Team $team;

    public function __construct(AtpClient $parent)
    {
        $this->atp = $parent;
        $this->moderation = new Ozone\Moderation($this);
        $this->server = new Ozone\Server($this);
        $this->team = new Ozone\Team($this);
    }
}
