<?php

namespace SocialDept\AtpClient\Client\Public;

class AtpPublicClient
{
    public PublicClient $client;
    public BskyPublicClient $bsky;
    public AtprotoPublicClient $atproto;

    public function __construct(string $serviceUrl)
    {
        $this->client = new PublicClient($serviceUrl);
        $this->bsky = new BskyPublicClient($this);
        $this->atproto = new AtprotoPublicClient($this);
    }
}
