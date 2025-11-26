<?php

namespace SocialDept\AtpClient\Client\Requests\Atproto;

use Illuminate\Http\UploadedFile;
use InvalidArgumentException;
use SocialDept\AtpClient\Attributes\RequiresScope;
use SocialDept\AtpClient\Auth\ScopeChecker;
use SocialDept\AtpClient\Client\Requests\Request;
use SocialDept\AtpClient\Enums\Scope;
use SocialDept\AtpClient\Http\Response;
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
    ): Response {
        $this->checkCollectionScope($collection, 'create');

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
    ): Response {
        $this->checkCollectionScope($collection, 'delete');

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
    ): Response {
        $this->checkCollectionScope($collection, 'update');

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
    ): Response {
        return $this->atp->client->get(
            endpoint: 'com.atproto.repo.getRecord',
            params: compact('repo', 'collection', 'rkey', 'cid')
        );
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
     * @requires transition:generic (rpc:com.atproto.repo.describeRepo)
     *
     * @see https://docs.bsky.app/docs/api/com-atproto-repo-describe-repo
     */
    #[RequiresScope(Scope::TransitionGeneric, granular: 'rpc:com.atproto.repo.describeRepo')]
    public function describeRepo(string $repo): Response
    {
        return $this->atp->client->get(
            endpoint: 'com.atproto.repo.describeRepo',
            params: compact('repo')
        );
    }

    /**
     * Check if the session has repo access for a specific collection and action.
     *
     * This check is in addition to the transition:generic scope check.
     * Users need either transition:generic OR the specific repo scope.
     */
    protected function checkCollectionScope(string $collection, string $action): void
    {
        $session = $this->atp->session();
        $checker = app(ScopeChecker::class);

        // If user has transition:generic, they have broad access
        if ($checker->hasScope($session, Scope::TransitionGeneric)) {
            return;
        }

        // Otherwise, check for specific repo scope
        $checker->checkRepoScopeOrFail($session, $collection, $action);
    }
}
