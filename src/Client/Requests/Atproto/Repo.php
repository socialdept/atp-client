<?php

namespace SocialDept\AtpClient\Client\Requests\Atproto;

use SocialDept\AtpClient\Client\Requests\Request;
use SocialDept\AtpClient\Http\Response;

class Repo extends Request
{
    /**
     * Create a record
     */
    public function createRecord(
        string $repo,
        string $collection,
        array $record,
        ?string $rkey = null,
        bool $validate = true,
        ?string $swapCommit = null
    ): Response {
        return $this->atp->client->post(
            endpoint: 'com.atproto.repo.createRecord',
            body: array_filter(
                compact('repo', 'collection', 'record', 'rkey', 'validate', 'swapCommit'),
                fn ($v) => ! is_null($v)
            )
        );
    }

    /**
     * Delete a record
     */
    public function deleteRecord(
        string $repo,
        string $collection,
        string $rkey,
        ?string $swapRecord = null,
        ?string $swapCommit = null
    ): Response {
        return $this->atp->client->post(
            endpoint: 'com.atproto.repo.deleteRecord',
            body: array_filter(
                compact('repo', 'collection', 'rkey', 'swapRecord', 'swapCommit'),
                fn ($v) => ! is_null($v)
            )
        );
    }

    /**
     * Put (upsert) a record
     */
    public function putRecord(
        string $repo,
        string $collection,
        string $rkey,
        array $record,
        bool $validate = true,
        ?string $swapRecord = null,
        ?string $swapCommit = null
    ): Response {
        return $this->atp->client->post(
            endpoint: 'com.atproto.repo.putRecord',
            body: array_filter(
                compact('repo', 'collection', 'rkey', 'record', 'validate', 'swapRecord', 'swapCommit'),
                fn ($v) => ! is_null($v)
            )
        );
    }

    /**
     * Get a record
     */
    public function getRecord(
        string $repo,
        string $collection,
        string $rkey,
        ?string $cid = null
    ): Response {
        return $this->atp->client->get(
            endpoint: 'com.atproto.repo.getRecord',
            params: compact('repo', 'collection', 'rkey', 'cid')
        );
    }

    /**
     * List records in a collection
     */
    public function listRecords(
        string $repo,
        string $collection,
        int $limit = 50,
        ?string $cursor = null,
        bool $reverse = false
    ): Response {
        return $this->atp->client->get(
            endpoint: 'com.atproto.repo.listRecords',
            params: compact('repo', 'collection', 'limit', 'cursor', 'reverse')
        );
    }

    /**
     * Upload a blob
     */
    public function uploadBlob(string $data, string $mimeType): Response
    {
        return $this->atp->client->post(
            endpoint: 'com.atproto.repo.uploadBlob',
            body: ['blob' => $data, 'mimeType' => $mimeType]
        );
    }

    /**
     * Describe the repository
     */
    public function describeRepo(string $repo): Response
    {
        return $this->atp->client->get(
            endpoint: 'com.atproto.repo.describeRepo',
            params: compact('repo')
        );
    }
}
