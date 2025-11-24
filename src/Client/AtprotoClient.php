<?php

namespace SocialDept\AtpClient\Client;

use Illuminate\Http\Client\Factory;
use SocialDept\AtpClient\Auth\DPoPNonceManager;
use SocialDept\AtpClient\Http\HasHttp;
use SocialDept\AtpClient\Http\Response;
use SocialDept\AtpClient\Session\SessionManager;

class AtprotoClient
{
    use HasHttp;

    public function __construct(
        SessionManager $sessions,
        Factory $http,
        string $identifier,
    ) {
        $this->sessions = $sessions;
        $this->http = $http;
        $this->identifier = $identifier;
        $this->nonceManager = app(DPoPNonceManager::class);
    }

    /**
     * Create a record
     */
    public function createRecord(string $repo, string $collection, array $record, ?string $rkey = null, bool $validate = true, ?string $swapCommit = null): Response
    {
        return $this->post('com.atproto.repo.createRecord', array_filter(compact('repo', 'collection', 'record', 'rkey', 'validate', 'swapCommit'), fn ($v) => ! is_null($v)));
    }

    /**
     * Delete a record
     */
    public function deleteRecord(string $repo, string $collection, string $rkey, ?string $swapRecord = null, ?string $swapCommit = null): Response
    {
        return $this->post('com.atproto.repo.deleteRecord', array_filter(compact('repo', 'collection', 'rkey', 'swapRecord', 'swapCommit'), fn ($v) => ! is_null($v)));
    }

    /**
     * Put (upsert) a record
     */
    public function putRecord(string $repo, string $collection, string $rkey, array $record, bool $validate = true, ?string $swapRecord = null, ?string $swapCommit = null): Response
    {
        return $this->post('com.atproto.repo.putRecord', array_filter(compact('repo', 'collection', 'rkey', 'record', 'validate', 'swapRecord', 'swapCommit'), fn ($v) => ! is_null($v)));
    }

    /**
     * Get a record
     */
    public function getRecord(string $repo, string $collection, string $rkey, ?string $cid = null): Response
    {
        return $this->get('com.atproto.repo.getRecord', compact('repo', 'collection', 'rkey', 'cid'));
    }

    /**
     * List records in a collection
     */
    public function listRecords(string $repo, string $collection, int $limit = 50, ?string $cursor = null, bool $reverse = false): Response
    {
        return $this->get('com.atproto.repo.listRecords', compact('repo', 'collection', 'limit', 'cursor', 'reverse'));
    }

    /**
     * Upload a blob
     */
    public function uploadBlob(string $data, string $mimeType): Response
    {
        return $this->post('com.atproto.repo.uploadBlob', ['blob' => $data, 'mimeType' => $mimeType]);
    }

    /**
     * Describe the repository
     */
    public function describeRepo(string $repo): Response
    {
        return $this->get('com.atproto.repo.describeRepo', compact('repo'));
    }

    /**
     * Get current session
     */
    public function getSession(): Response
    {
        return $this->get('com.atproto.server.getSession');
    }

    /**
     * Describe server
     */
    public function describeServer(): Response
    {
        return $this->get('com.atproto.server.describeServer');
    }

    /**
     * Resolve handle to DID
     */
    public function resolveHandle(string $handle): Response
    {
        return $this->get('com.atproto.identity.resolveHandle', compact('handle'));
    }

    /**
     * Update handle
     */
    public function updateHandle(string $handle): Response
    {
        return $this->post('com.atproto.identity.updateHandle', compact('handle'));
    }

    /**
     * Get blob from sync
     */
    public function getBlob(string $did, string $cid): Response
    {
        return $this->get('com.atproto.sync.getBlob', compact('did', 'cid'));
    }

    /**
     * Get checkout from sync
     */
    public function getCheckout(string $did): Response
    {
        return $this->get('com.atproto.sync.getCheckout', compact('did'));
    }

    /**
     * Get commit path from sync
     */
    public function getCommitPath(string $did, ?string $latest = null, ?string $earliest = null): Response
    {
        return $this->get('com.atproto.sync.getCommitPath', compact('did', 'latest', 'earliest'));
    }

    /**
     * Get repo from sync
     */
    public function getRepo(string $did, ?string $since = null): Response
    {
        return $this->get('com.atproto.sync.getRepo', compact('did', 'since'));
    }

    /**
     * List repos from sync
     */
    public function listRepos(int $limit = 500, ?string $cursor = null): Response
    {
        return $this->get('com.atproto.sync.listRepos', compact('limit', 'cursor'));
    }
}
