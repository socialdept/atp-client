<?php

namespace SocialDept\AtpClient\Client\Requests\Atproto;

use SocialDept\AtpClient\Client\Requests\Request;
use SocialDept\AtpClient\Http\Response;

class SyncRequestClient extends Request
{
    /**
     * Get a blob associated with a given account
     *
     * @see https://docs.bsky.app/docs/api/com-atproto-sync-get-blob
     */
    public function getBlob(string $did, string $cid): Response
    {
        return $this->atp->client->get(
            endpoint: 'com.atproto.sync.getBlob',
            params: compact('did', 'cid')
        );
    }

    /**
     * Download a repository export as CAR file
     *
     * @see https://docs.bsky.app/docs/api/com-atproto-sync-get-repo
     */
    public function getRepo(string $did, ?string $since = null): Response
    {
        return $this->atp->client->get(
            endpoint: 'com.atproto.sync.getRepo',
            params: compact('did', 'since')
        );
    }

    /**
     * Enumerates all the DID, rev, and commit CID for all repos hosted by this service
     *
     * @see https://docs.bsky.app/docs/api/com-atproto-sync-list-repos
     */
    public function listRepos(int $limit = 500, ?string $cursor = null): Response
    {
        return $this->atp->client->get(
            endpoint: 'com.atproto.sync.listRepos',
            params: compact('limit', 'cursor')
        );
    }

    /**
     * Get the current commit CID & revision of the specified repo
     *
     * @see https://docs.bsky.app/docs/api/com-atproto-sync-get-latest-commit
     */
    public function getLatestCommit(string $did): Response
    {
        return $this->atp->client->get(
            endpoint: 'com.atproto.sync.getLatestCommit',
            params: compact('did')
        );
    }

    /**
     * Get data blocks needed to prove the existence or non-existence of record
     *
     * @see https://docs.bsky.app/docs/api/com-atproto-sync-get-record
     */
    public function getRecord(string $did, string $collection, string $rkey): Response
    {
        return $this->atp->client->get(
            endpoint: 'com.atproto.sync.getRecord',
            params: compact('did', 'collection', 'rkey')
        );
    }

    /**
     * List blob CIDs for an account, since some repo revision
     *
     * @see https://docs.bsky.app/docs/api/com-atproto-sync-list-blobs
     */
    public function listBlobs(
        string $did,
        ?string $since = null,
        int $limit = 500,
        ?string $cursor = null
    ): Response {
        return $this->atp->client->get(
            endpoint: 'com.atproto.sync.listBlobs',
            params: compact('did', 'since', 'limit', 'cursor')
        );
    }

    /**
     * Get data blocks from a given repo, by CID
     *
     * @see https://docs.bsky.app/docs/api/com-atproto-sync-get-blocks
     */
    public function getBlocks(string $did, array $cids): Response
    {
        return $this->atp->client->get(
            endpoint: 'com.atproto.sync.getBlocks',
            params: compact('did', 'cids')
        );
    }

    /**
     * Get the hosting status for a repository, on this server
     *
     * @see https://docs.bsky.app/docs/api/com-atproto-sync-get-repo-status
     */
    public function getRepoStatus(string $did): Response
    {
        return $this->atp->client->get(
            endpoint: 'com.atproto.sync.getRepoStatus',
            params: compact('did')
        );
    }
}
