<?php

namespace SocialDept\AtpClient\Client\Requests\Atproto;

use Illuminate\Http\UploadedFile;
use InvalidArgumentException;
use SocialDept\AtpClient\Attributes\RequiresScope;
use SocialDept\AtpClient\Auth\ScopeChecker;
use SocialDept\AtpClient\Client\Requests\Request;
use SocialDept\AtpClient\Data\Responses\Atproto\Repo\CreateRecordResponse;
use SocialDept\AtpClient\Data\Responses\Atproto\Repo\DeleteRecordResponse;
use SocialDept\AtpClient\Data\Responses\Atproto\Repo\DescribeRepoResponse;
use SocialDept\AtpClient\Data\Responses\Atproto\Repo\GetRecordResponse;
use SocialDept\AtpClient\Data\Responses\Atproto\Repo\ListRecordsResponse;
use SocialDept\AtpClient\Data\Responses\Atproto\Repo\PutRecordResponse;
use SocialDept\AtpClient\Enums\Nsid\AtprotoRepo;
use SocialDept\AtpClient\Enums\Scope;
use SocialDept\AtpSchema\Data\BlobReference;
use SplFileInfo;
use Throwable;

class RepoRequestClient extends Request
{
    /**
     * Create a record
     *
     * @requires transition:generic OR repo:[collection]?action=create
     *
     * @see https://docs.bsky.app/docs/api/com-atproto-repo-create-record
     */
    #[RequiresScope(Scope::TransitionGeneric, description: 'Create records in repository')]
    public function createRecord(
        string $repo,
        string $collection,
        array $record,
        ?string $rkey = null,
        bool $validate = true,
        ?string $swapCommit = null
    ): CreateRecordResponse {
        $this->checkCollectionScope($collection, 'create');

        $response = $this->atp->client->post(
            endpoint: AtprotoRepo::CreateRecord,
            body: array_filter(
                compact('repo', 'collection', 'record', 'rkey', 'validate', 'swapCommit'),
                fn ($v) => ! is_null($v)
            )
        );

        return CreateRecordResponse::fromArray($response->json());
    }

    /**
     * Delete a record
     *
     * @requires transition:generic OR repo:[collection]?action=delete
     *
     * @see https://docs.bsky.app/docs/api/com-atproto-repo-delete-record
     */
    #[RequiresScope(Scope::TransitionGeneric, description: 'Delete records from repository')]
    public function deleteRecord(
        string $repo,
        string $collection,
        string $rkey,
        ?string $swapRecord = null,
        ?string $swapCommit = null
    ): DeleteRecordResponse {
        $this->checkCollectionScope($collection, 'delete');

        $response = $this->atp->client->post(
            endpoint: AtprotoRepo::DeleteRecord,
            body: array_filter(
                compact('repo', 'collection', 'rkey', 'swapRecord', 'swapCommit'),
                fn ($v) => ! is_null($v)
            )
        );

        return DeleteRecordResponse::fromArray($response->json());
    }

    /**
     * Put (upsert) a record
     *
     * @requires transition:generic OR repo:[collection]?action=update
     *
     * @see https://docs.bsky.app/docs/api/com-atproto-repo-put-record
     */
    #[RequiresScope(Scope::TransitionGeneric, description: 'Update records in repository')]
    public function putRecord(
        string $repo,
        string $collection,
        string $rkey,
        array $record,
        bool $validate = true,
        ?string $swapRecord = null,
        ?string $swapCommit = null
    ): PutRecordResponse {
        $this->checkCollectionScope($collection, 'update');

        $response = $this->atp->client->post(
            endpoint: AtprotoRepo::PutRecord,
            body: array_filter(
                compact('repo', 'collection', 'rkey', 'record', 'validate', 'swapRecord', 'swapCommit'),
                fn ($v) => ! is_null($v)
            )
        );

        return PutRecordResponse::fromArray($response->json());
    }

    /**
     * Get a record
     *
     * @requires transition:generic (rpc:com.atproto.repo.getRecord)
     *
     * @see https://docs.bsky.app/docs/api/com-atproto-repo-get-record
     */
    #[RequiresScope(Scope::TransitionGeneric, granular: 'rpc:com.atproto.repo.getRecord')]
    public function getRecord(
        string $repo,
        string $collection,
        string $rkey,
        ?string $cid = null
    ): GetRecordResponse {
        $response = $this->atp->client->get(
            endpoint: AtprotoRepo::GetRecord,
            params: compact('repo', 'collection', 'rkey', 'cid')
        );

        return GetRecordResponse::fromArray($response->json());
    }

    /**
     * List records in a collection
     *
     * @requires transition:generic (rpc:com.atproto.repo.listRecords)
     *
     * @see https://docs.bsky.app/docs/api/com-atproto-repo-list-records
     */
    #[RequiresScope(Scope::TransitionGeneric, granular: 'rpc:com.atproto.repo.listRecords')]
    public function listRecords(
        string $repo,
        string $collection,
        int $limit = 50,
        ?string $cursor = null,
        bool $reverse = false
    ): ListRecordsResponse {
        $response = $this->atp->client->get(
            endpoint: AtprotoRepo::ListRecords,
            params: compact('repo', 'collection', 'limit', 'cursor', 'reverse')
        );

        return ListRecordsResponse::fromArray($response->json());
    }

    /**
     * Upload a new blob, to be referenced from a repository record
     *
     * The blob will be deleted if it is not referenced within a time window.
     *
     * @requires transition:generic (blob:*\/*\)
     *
     * @param  UploadedFile|SplFileInfo|string  $file  The file to upload
     * @param  string|null  $mimeType  MIME type (required for string input, auto-detected for file objects)
     *
     * @throws InvalidArgumentException|Throwable  When $file is a string and $mimeType is not provided
     *
     * @see https://docs.bsky.app/docs/api/com-atproto-repo-upload-blob
     */
    #[RequiresScope(Scope::TransitionGeneric, granular: 'blob:*/*')]
    public function uploadBlob(UploadedFile|SplFileInfo|string $file, ?string $mimeType = null): BlobReference
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

        $response = $this->atp->client->postBlob(
            endpoint: AtprotoRepo::UploadBlob,
            data: $data,
            mimeType: $mimeType
        );

        return BlobReference::fromArray($response->json()['blob']);
    }

    /**
     * Describe the repository
     *
     * @requires transition:generic (rpc:com.atproto.repo.describeRepo)
     *
     * @see https://docs.bsky.app/docs/api/com-atproto-repo-describe-repo
     */
    #[RequiresScope(Scope::TransitionGeneric, granular: 'rpc:com.atproto.repo.describeRepo')]
    public function describeRepo(string $repo): DescribeRepoResponse
    {
        $response = $this->atp->client->get(
            endpoint: AtprotoRepo::DescribeRepo,
            params: compact('repo')
        );

        return DescribeRepoResponse::fromArray($response->json());
    }

    /**
     * Check if the session has repo access for a specific collection and action.
     *
     * This check is in addition to the transition:generic scope check.
     * Users need either transition:generic OR the specific repo scope.
     */
    protected function checkCollectionScope(string $collection, string $action): void
    {
        $session = $this->atp->client->session();
        $checker = app(ScopeChecker::class);

        // If user has transition:generic, they have broad access
        if ($checker->hasScope($session, Scope::TransitionGeneric)) {
            return;
        }

        // Otherwise, check for specific repo scope
        $checker->checkRepoScopeOrFail($session, $collection, $action);
    }
}
