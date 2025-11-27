<?php

namespace SocialDept\AtpClient\Client\Public;

use SocialDept\AtpClient\Client\Public\Requests\Bsky\ActorPublicRequestClient;
use SocialDept\AtpClient\Client\Public\Requests\Bsky\FeedPublicRequestClient;
use SocialDept\AtpClient\Client\Public\Requests\Bsky\GraphPublicRequestClient;
use SocialDept\AtpClient\Client\Public\Requests\Bsky\LabelerPublicRequestClient;
use SocialDept\AtpClient\Concerns\HasDomainExtensions;

class BskyPublicClient
{
    use HasDomainExtensions;

    protected AtpPublicClient $atp;
    public ActorPublicRequestClient $actor;
    public FeedPublicRequestClient $feed;
    public GraphPublicRequestClient $graph;
    public LabelerPublicRequestClient $labeler;

    public function __construct(AtpPublicClient $parent)
    {
        $this->atp = $parent;
        $this->actor = new ActorPublicRequestClient($this);
        $this->feed = new FeedPublicRequestClient($this);
        $this->graph = new GraphPublicRequestClient($this);
        $this->labeler = new LabelerPublicRequestClient($this);
    }

    protected function getDomainName(): string
    {
        return 'bsky';
    }

    protected function getRootClientClass(): string
    {
        return AtpPublicClient::class;
    }
}
