<?php

namespace SocialDept\AtpClient\Client;

use SocialDept\AtpClient\AtpClient;
use SocialDept\AtpClient\Client\Records\FollowRecordClient;
use SocialDept\AtpClient\Client\Records\LikeRecordClient;
use SocialDept\AtpClient\Client\Records\PostRecordClient;
use SocialDept\AtpClient\Client\Records\ProfileRecordClient;
use SocialDept\AtpClient\Client\Requests\Bsky;
use SocialDept\AtpClient\Concerns\HasDomainExtensions;

class BskyClient
{
    use HasDomainExtensions;

    /**
     * The parent AtpClient instance
     */
    protected AtpClient $atp;

    /**
     * Feed operations (app.bsky.feed.*)
     */
    public Bsky\FeedRequestClient $feed;

    /**
     * Actor operations (app.bsky.actor.*)
     */
    public Bsky\ActorRequestClient $actor;

    /**
     * Graph operations (app.bsky.graph.*)
     */
    public Bsky\GraphRequestClient $graph;

    /**
     * Labeler operations (app.bsky.labeler.*)
     */
    public Bsky\LabelerRequestClient $labeler;

    /**
     * Post record client
     */
    public PostRecordClient $post;

    /**
     * Profile record client
     */
    public ProfileRecordClient $profile;

    /**
     * Like record client
     */
    public LikeRecordClient $like;

    /**
     * Follow record client
     */
    public FollowRecordClient $follow;

    public function __construct(AtpClient $parent)
    {
        $this->atp = $parent;
        $this->feed = new Bsky\FeedRequestClient($this);
        $this->actor = new Bsky\ActorRequestClient($this);
        $this->graph = new Bsky\GraphRequestClient($this);
        $this->labeler = new Bsky\LabelerRequestClient($this);
        $this->post = new PostRecordClient($this);
        $this->profile = new ProfileRecordClient($this);
        $this->like = new LikeRecordClient($this);
        $this->follow = new FollowRecordClient($this);
    }

    protected function getDomainName(): string
    {
        return 'bsky';
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
