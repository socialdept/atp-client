<?php

namespace SocialDept\AtpClient\Client\Requests\Atproto;

use SocialDept\AtpClient\Attributes\RequiresScope;
use SocialDept\AtpClient\Client\Requests\Request;
use SocialDept\AtpClient\Enums\Scope;
use SocialDept\AtpClient\Http\Response;

class SyncRequestClient extends Request
{
    /**
     * Get a blob associated with a given account
     *
     * @requires atproto (rpc:com.atproto.sync.getBlob)
     *
     * @see https://docs.bsky.app/docs/api/com-atproto-sync-get-blob
     */
    #[RequiresScope(Scope::Atproto, granular: 'rpc:com.atproto.sync.getBlob')]
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
     * @requires atproto (rpc:com.atproto.sync.getRepo)
     *
     * @see https://docs.bsky.app/docs/api/com-atproto-sync-get-repo
     */
    #[RequiresScope(Scope::Atproto, granular: 'rpc:com.atproto.sync.getRepo')]
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
     * @requires atproto (rpc:com.atproto.sync.listRepos)
     *
     * @see https://docs.bsky.app/docs/api/com-atproto-sync-list-repos
     */
    #[RequiresScope(Scope::Atproto, granular: 'rpc:com.atproto.sync.listRepos')]
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
     * @requires atproto (rpc:com.atproto.sync.getLatestCommit)
     *
     * @see https://docs.bsky.app/docs/api/com-atproto-sync-get-latest-commit
     */
    #[RequiresScope(Scope::Atproto, granular: 'rpc:com.atproto.sync.getLatestCommit')]
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
     * @requires atproto (rpc:com.atproto.sync.getRecord)
     *
     * @see https://docs.bsky.app/docs/api/com-atproto-sync-get-record
     */
    #[RequiresScope(Scope::Atproto, granular: 'rpc:com.atproto.sync.getRecord')]
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
     * @requires atproto (rpc:com.atproto.sync.listBlobs)
     *
     * @see https://docs.bsky.app/docs/api/com-atproto-sync-list-blobs
     */
    #[RequiresScope(Scope::Atproto, granular: 'rpc:com.atproto.sync.listBlobs')]
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
     * @requires atproto (rpc:com.atproto.sync.getBlocks)
     *
     * @see https://docs.bsky.app/docs/api/com-atproto-sync-get-blocks
     */
    #[RequiresScope(Scope::Atproto, granular: 'rpc:com.atproto.sync.getBlocks')]
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
     * @requires atproto (rpc:com.atproto.sync.getRepoStatus)
     *
     * @see https://docs.bsky.app/docs/api/com-atproto-sync-get-repo-status
     */
    #[RequiresScope(Scope::Atproto, granular: 'rpc:com.atproto.sync.getRepoStatus')]
    public function getRepoStatus(string $did): Response
    {
        return $this->atp->client->get(
            endpoint: 'com.atproto.sync.getRepoStatus',
            params: compact('did')
        );
    }
}
