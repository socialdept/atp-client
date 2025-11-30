<?php

namespace SocialDept\AtpClient\Client\Records;

use DateTimeInterface;
use SocialDept\AtpClient\Attributes\ScopedEndpoint;
use SocialDept\AtpClient\Client\Requests\Request;
use SocialDept\AtpClient\Data\StrongRef;
use SocialDept\AtpClient\Enums\Nsid\BskyGraph;
use SocialDept\AtpClient\Enums\Scope;

class FollowRecordClient extends Request
{
    /**
     * Follow a user
     *
     * @requires transition:generic OR (rpc:com.atproto.repo.createRecord AND repo:app.bsky.graph.follow?action=create)
     */
    #[ScopedEndpoint(Scope::TransitionGeneric, granular: 'rpc:com.atproto.repo.createRecord')]
    #[ScopedEndpoint(Scope::TransitionGeneric, granular: 'repo:app.bsky.graph.follow?action=create')]
    public function create(
        string $subject,
        ?DateTimeInterface $createdAt = null
    ): StrongRef {
        $record = [
            '$type' => BskyGraph::Follow->value,
            'subject' => $subject, // DID
            'createdAt' => ($createdAt ?? now())->format('c'),
        ];

        $response = $this->atp->atproto->repo->createRecord(
            repo: $this->atp->client->session()->did(),
            collection: BskyGraph::Follow,
            record: $record
        );

        return StrongRef::fromResponse($response->json());
    }

    /**
     * Unfollow a user (delete follow record)
     *
     * @requires transition:generic OR (rpc:com.atproto.repo.deleteRecord AND repo:app.bsky.graph.follow?action=delete)
     */
    #[ScopedEndpoint(Scope::TransitionGeneric, granular: 'rpc:com.atproto.repo.deleteRecord')]
    #[ScopedEndpoint(Scope::TransitionGeneric, granular: 'repo:app.bsky.graph.follow?action=delete')]
    public function delete(string $rkey): void
    {
        $this->atp->atproto->repo->deleteRecord(
            repo: $this->atp->client->session()->did(),
            collection: BskyGraph::Follow,
            rkey: $rkey
        );
    }

    /**
     * Get a follow record
     *
     * @requires transition:generic (rpc:com.atproto.repo.getRecord)
     */
    #[ScopedEndpoint(Scope::TransitionGeneric, granular: 'rpc:com.atproto.repo.getRecord')]
    public function get(string $rkey, ?string $cid = null): array
    {
        $response = $this->atp->atproto->repo->getRecord(
            repo: $this->atp->client->session()->did(),
            collection: BskyGraph::Follow,
            rkey: $rkey,
            cid: $cid
        );

        return $response->json('value');
    }
}
