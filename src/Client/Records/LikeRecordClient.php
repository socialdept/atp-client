<?php

namespace SocialDept\AtpClient\Client\Records;

use DateTimeInterface;
use SocialDept\AtpClient\Attributes\RequiresScope;
use SocialDept\AtpClient\Client\Requests\Request;
use SocialDept\AtpClient\Data\StrongRef;
use SocialDept\AtpClient\Enums\Scope;

class LikeRecordClient extends Request
{
    /**
     * Like a post
     *
     * @requires transition:generic OR (rpc:com.atproto.repo.createRecord AND repo:app.bsky.feed.like?action=create)
     */
    #[RequiresScope(Scope::TransitionGeneric, granular: 'rpc:com.atproto.repo.createRecord')]
    #[RequiresScope(Scope::TransitionGeneric, granular: 'repo:app.bsky.feed.like?action=create')]
    public function create(
        StrongRef $subject,
        ?DateTimeInterface $createdAt = null
    ): StrongRef {
        $record = [
            '$type' => 'app.bsky.feed.like',
            'subject' => $subject->toArray(),
            'createdAt' => ($createdAt ?? now())->format('c'),
        ];

        $response = $this->atp->atproto->repo->createRecord(
            repo: $this->atp->client->session()->did(),
            collection: 'app.bsky.feed.like',
            record: $record
        );

        return StrongRef::fromResponse($response->json());
    }

    /**
     * Unlike a post (delete like record)
     *
     * @requires transition:generic OR (rpc:com.atproto.repo.deleteRecord AND repo:app.bsky.feed.like?action=delete)
     */
    #[RequiresScope(Scope::TransitionGeneric, granular: 'rpc:com.atproto.repo.deleteRecord')]
    #[RequiresScope(Scope::TransitionGeneric, granular: 'repo:app.bsky.feed.like?action=delete')]
    public function delete(string $rkey): void
    {
        $this->atp->atproto->repo->deleteRecord(
            repo: $this->atp->client->session()->did(),
            collection: 'app.bsky.feed.like',
            rkey: $rkey
        );
    }

    /**
     * Get a like record
     *
     * @requires transition:generic (rpc:com.atproto.repo.getRecord)
     */
    #[RequiresScope(Scope::TransitionGeneric, granular: 'rpc:com.atproto.repo.getRecord')]
    public function get(string $rkey, ?string $cid = null): array
    {
        $response = $this->atp->atproto->repo->getRecord(
            repo: $this->atp->client->session()->did(),
            collection: 'app.bsky.feed.like',
            rkey: $rkey,
            cid: $cid
        );

        return $response->json('value');
    }
}
