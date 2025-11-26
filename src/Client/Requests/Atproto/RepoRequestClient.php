<?php

namespace SocialDept\AtpClient\Client\Requests\Atproto;

use Illuminate\Http\UploadedFile;
use InvalidArgumentException;
use SocialDept\AtpClient\Client\Requests\Request;
use SocialDept\AtpClient\Http\Response;
use SplFileInfo;
use Throwable;

class RepoRequestClient extends Request
{
    /**
     * Create a record
     *
     * @see https://docs.bsky.app/docs/api/com-atproto-repo-create-record
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
     *
     * @see https://docs.bsky.app/docs/api/com-atproto-repo-delete-record
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
     *
     * @see https://docs.bsky.app/docs/api/com-atproto-repo-put-record
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
     *
     * @see https://docs.bsky.app/docs/api/com-atproto-repo-get-record
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
     *
     * @see https://docs.bsky.app/docs/api/com-atproto-repo-list-records
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
     * Upload a new blob, to be referenced from a repository record
     *
     * The blob will be deleted if it is not referenced within a time window.
     *
     * @param  UploadedFile|SplFileInfo|string  $file  The file to upload
     * @param  string|null  $mimeType  MIME type (required for string input, auto-detected for file objects)
     *
     * @throws InvalidArgumentException|Throwable  When $file is a string and $mimeType is not provided
     *
     * @see https://docs.bsky.app/docs/api/com-atproto-repo-upload-blob
     */
    public function uploadBlob(UploadedFile|SplFileInfo|string $file, ?string $mimeType = null): Response
    {
        // Handle different input types
        if ($file instanceof UploadedFile) {
            $data = $file->getContent();
            $mimeType ??= $file->getMimeType();
        } elseif ($file instanceof SplFileInfo) {
            $data = file_get_contents($file->getRealPath());
            $mimeType ??= mime_content_type($file->getRealPath()) ?: 'application/octet-stream';
        } else {
            throw_if($mimeType === null, new InvalidArgumentException('The $mimeType parameter is required when $file is a string.'));
            $data = $file;
        }

        return $this->atp->client->postBlob(
            endpoint: 'com.atproto.repo.uploadBlob',
            data: $data,
            mimeType: $mimeType
        );
    }

    /**
     * Describe the repository
     *
     * @see https://docs.bsky.app/docs/api/com-atproto-repo-describe-repo
     */
    public function describeRepo(string $repo): Response
    {
        return $this->atp->client->get(
            endpoint: 'com.atproto.repo.describeRepo',
            params: compact('repo')
        );
    }
}
