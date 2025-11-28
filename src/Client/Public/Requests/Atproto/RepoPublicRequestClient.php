<?php

namespace SocialDept\AtpClient\Client\Public\Requests\Atproto;

use SocialDept\AtpClient\Client\Public\Requests\PublicRequest;
use SocialDept\AtpClient\Data\Responses\Atproto\Repo\DescribeRepoResponse;
use SocialDept\AtpClient\Data\Responses\Atproto\Repo\GetRecordResponse;
use SocialDept\AtpClient\Data\Responses\Atproto\Repo\ListRecordsResponse;
use SocialDept\AtpClient\Enums\Nsid\AtprotoRepo;

class RepoPublicRequestClient extends PublicRequest
{
    /**
     * Get a record
     *
     * @see https://docs.bsky.app/docs/api/com-atproto-repo-get-record
     */
    public function getRecord(
        string $repo,
        string $collection,
        string $rkey,
        ?string $cid = null
    ): GetRecordResponse {
        $response = $this->atp->client->get(
            AtprotoRepo::GetRecord,
            compact('repo', 'collection', 'rkey', 'cid')
        );

        return GetRecordResponse::fromArray($response->json());
    }

    /**
     * List records in a collection
     *
     * @see https://docs.bsky.app/docs/api/com-atproto-repo-list-records
     */
    public function listRecords(
        string $repo,
        string $collection,
        int $limit = 50,
        ?string $cursor = null,
        bool $reverse = false
    ): ListRecordsResponse {
        $response = $this->atp->client->get(
            AtprotoRepo::ListRecords,
            compact('repo', 'collection', 'limit', 'cursor', 'reverse')
        );

        return ListRecordsResponse::fromArray($response->json());
    }

    /**
     * Describe the repository
     *
     * @see https://docs.bsky.app/docs/api/com-atproto-repo-describe-repo
     */
    public function describeRepo(string $repo): DescribeRepoResponse
    {
        $response = $this->atp->client->get(
            AtprotoRepo::DescribeRepo,
            compact('repo')
        );

        return DescribeRepoResponse::fromArray($response->json());
    }
}
