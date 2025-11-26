<?php

namespace SocialDept\AtpClient\Client;

use SocialDept\AtpClient\AtpClient;
use SocialDept\AtpClient\Client\Records\FollowRecordClient;
use SocialDept\AtpClient\Client\Records\LikeRecordClient;
use SocialDept\AtpClient\Client\Records\PostRecordClient;
use SocialDept\AtpClient\Client\Records\ProfileRecordClient;
use SocialDept\AtpClient\Client\Requests\Bsky;

class BskyClient
{
    /**
     * The parent AtpClient instance
     */
    public AtpClient $atp;

    /**
     * Feed operations (app.bsky.feed.*)
     */
    public Bsky\FeedRequestClient $feed;

    /**
     * Actor operations (app.bsky.actor.*)
     */
    public Bsky\ActorRequestClient $actor;

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
        $this->post = new PostRecordClient($this);
        $this->profile = new ProfileRecordClient($this);
        $this->like = new LikeRecordClient($this);
        $this->follow = new FollowRecordClient($this);
    }
}
