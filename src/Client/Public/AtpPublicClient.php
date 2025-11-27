<?php

namespace SocialDept\AtpClient\Client\Public;

use SocialDept\AtpClient\Concerns\HasExtensions;

class AtpPublicClient
{
    use HasExtensions;
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
