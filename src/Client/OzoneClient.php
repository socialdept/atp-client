<?php

namespace SocialDept\AtpClient\Client;

use SocialDept\AtpClient\AtpClient;
use SocialDept\AtpClient\Client\Requests\Ozone;
use SocialDept\AtpClient\Concerns\HasDomainExtensions;

class OzoneClient
{
    use HasDomainExtensions;
    /**
     * The parent AtpClient instance
     */
    protected AtpClient $atp;

    /**
     * Moderation operations (tools.ozone.moderation.*)
     */
    public Ozone\ModerationRequestClient $moderation;

    /**
     * Server operations (tools.ozone.server.*)
     */
    public Ozone\ServerRequestClient $server;

    /**
     * Team operations (tools.ozone.team.*)
     */
    public Ozone\TeamRequestClient $team;

    public function __construct(AtpClient $parent)
    {
        $this->atp = $parent;
        $this->moderation = new Ozone\ModerationRequestClient($this);
        $this->server = new Ozone\ServerRequestClient($this);
        $this->team = new Ozone\TeamRequestClient($this);
    }

    protected function getDomainName(): string
    {
        return 'ozone';
    }

    protected function getRootClientClass(): string
    {
        return AtpClient::class;
    }
}
