<?php

namespace SocialDept\AtpClient\Client\Requests\Atproto;

use BackedEnum;
use SocialDept\AtpClient\Attributes\PublicEndpoint;
use SocialDept\AtpClient\Client\Requests\Request;
use SocialDept\AtpClient\Data\Responses\Atproto\Sync\GetRepoStatusResponse;
use SocialDept\AtpClient\Data\Responses\Atproto\Sync\ListBlobsResponse;
use SocialDept\AtpClient\Data\Responses\Atproto\Sync\ListReposByCollectionResponse;
use SocialDept\AtpClient\Data\Responses\Atproto\Sync\ListReposResponse;
use SocialDept\AtpClient\Enums\Nsid\AtprotoSync;
use SocialDept\AtpClient\Http\Response;
use SocialDept\AtpSchema\Generated\Com\Atproto\Repo\Defs\CommitMeta;

class SyncRequestClient extends Request
{
    /**
     * Get a blob associated with a given account
     *
     * @see https://docs.bsky.app/docs/api/com-atproto-sync-get-blob
     */
    #[PublicEndpoint]
    public function getBlob(string $did, string $cid): Response
    {
        return $this->atp->client->get(
            endpoint: AtprotoSync::GetBlob,
            params: compact('did', 'cid')
        );
    }

    /**
     * Download a repository export as CAR file
     *
     * @see https://docs.bsky.app/docs/api/com-atproto-sync-get-repo
     */
    #[PublicEndpoint]
    public function getRepo(string $did, ?string $since = null): Response
    {
        return $this->atp->client->get(
            endpoint: AtprotoSync::GetRepo,
            params: compact('did', 'since')
        );
    }

    /**
     * Enumerates all the DID, rev, and commit CID for all repos hosted by this service
     *
     * @see https://docs.bsky.app/docs/api/com-atproto-sync-list-repos
     */
    #[PublicEndpoint]
    public function listRepos(int $limit = 500, ?string $cursor = null): ListReposResponse
    {
        $response = $this->atp->client->get(
            endpoint: AtprotoSync::ListRepos,
            params: compact('limit', 'cursor')
        );

        return ListReposResponse::fromArray($response->json());
    }

    /**
     * Enumerates all the DIDs with records in a specific collection
     *
     * @see https://docs.bsky.app/docs/api/com-atproto-sync-list-repos-by-collection
     */
    #[PublicEndpoint]
    public function listReposByCollection(
        string|BackedEnum $collection,
        int $limit = 500,
        ?string $cursor = null
    ): ListReposByCollectionResponse {
        $collection = $collection instanceof BackedEnum ? $collection->value : $collection;

        $response = $this->atp->client->get(
            endpoint: AtprotoSync::ListReposByCollection,
            params: compact('collection', 'limit', 'cursor')
        );

        return ListReposByCollectionResponse::fromArray($response->json());
    }

    /**
     * Get the current commit CID & revision of the specified repo
     *
     * @see https://docs.bsky.app/docs/api/com-atproto-sync-get-latest-commit
     */
    #[PublicEndpoint]
    public function getLatestCommit(string $did): CommitMeta
    {
        $response = $this->atp->client->get(
            endpoint: AtprotoSync::GetLatestCommit,
            params: compact('did')
        );

        return CommitMeta::fromArray($response->json());
    }

    /**
     * Get data blocks needed to prove the existence or non-existence of record
     *
     * @see https://docs.bsky.app/docs/api/com-atproto-sync-get-record
     */
    #[PublicEndpoint]
    public function getRecord(string $did, string|BackedEnum $collection, string $rkey): Response
    {
        $collection = $collection instanceof BackedEnum ? $collection->value : $collection;

        return $this->atp->client->get(
            endpoint: AtprotoSync::GetRecord,
            params: compact('did', 'collection', 'rkey')
        );
    }

    /**
     * List blob CIDs for an account, since some repo revision
     *
     * @see https://docs.bsky.app/docs/api/com-atproto-sync-list-blobs
     */
    #[PublicEndpoint]
    public function listBlobs(
        string $did,
        ?string $since = null,
        int $limit = 500,
        ?string $cursor = null
    ): ListBlobsResponse {
        $response = $this->atp->client->get(
            endpoint: AtprotoSync::ListBlobs,
            params: compact('did', 'since', 'limit', 'cursor')
        );

        return ListBlobsResponse::fromArray($response->json());
    }

    /**
     * Get data blocks from a given repo, by CID
     *
     * @see https://docs.bsky.app/docs/api/com-atproto-sync-get-blocks
     */
    #[PublicEndpoint]
    public function getBlocks(string $did, array $cids): Response
    {
        return $this->atp->client->get(
            endpoint: AtprotoSync::GetBlocks,
            params: compact('did', 'cids')
        );
    }

    /**
     * Get the hosting status for a repository, on this server
     *
     * @see https://docs.bsky.app/docs/api/com-atproto-sync-get-repo-status
     */
    #[PublicEndpoint]
    public function getRepoStatus(string $did): GetRepoStatusResponse
    {
        $response = $this->atp->client->get(
            endpoint: AtprotoSync::GetRepoStatus,
            params: compact('did')
        );

        return GetRepoStatusResponse::fromArray($response->json());
    }
}
