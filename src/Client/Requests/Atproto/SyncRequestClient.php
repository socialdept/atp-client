<?php

namespace SocialDept\AtpClient\Client\Requests\Atproto;

use SocialDept\AtpClient\Client\Requests\Request;
use SocialDept\AtpClient\Http\Response;

class SyncRequestClient extends Request
{
    /**
     * Get blob from sync
     */
    public function getBlob(string $did, string $cid): Response
    {
        return $this->atp->client->get(
            endpoint: 'com.atproto.sync.getBlob',
            params: compact('did', 'cid')
        );
    }

    /**
     * Get checkout from sync
     */
    public function getCheckout(string $did): Response
    {
        return $this->atp->client->get(
            endpoint: 'com.atproto.sync.getCheckout',
            params: compact('did')
        );
    }

    /**
     * Get commit path from sync
     */
    public function getCommitPath(
        string $did,
        ?string $latest = null,
        ?string $earliest = null
    ): Response {
        return $this->atp->client->get(
            endpoint: 'com.atproto.sync.getCommitPath',
            params: compact('did', 'latest', 'earliest')
        );
    }

    /**
     * Get repo from sync
     */
    public function getRepo(string $did, ?string $since = null): Response
    {
        return $this->atp->client->get(
            endpoint: 'com.atproto.sync.getRepo',
            params: compact('did', 'since')
        );
    }

    /**
     * List repos from sync
     */
    public function listRepos(int $limit = 500, ?string $cursor = null): Response
    {
        return $this->atp->client->get(
            endpoint: 'com.atproto.sync.listRepos',
            params: compact('limit', 'cursor')
        );
    }
}
