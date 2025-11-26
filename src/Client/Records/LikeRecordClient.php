<?php

namespace SocialDept\AtpClient\Client\Records;

use DateTimeInterface;
use SocialDept\AtpClient\Client\Requests\Request;
use SocialDept\AtpClient\Data\StrongRef;

class LikeRecordClient extends Request
{
    /**
     * Like a post
     */
    public function create(
        StrongRef $subject,
        ?DateTimeInterface $createdAt = null
    ): StrongRef {
        $record = [
            '$type' => 'app.bsky.feed.like',
            'subject' => $subject->toArray(),
            'createdAt' => ($createdAt ?? now())->format('c'),
        ];

        $response = $this->atp->client->post(
            endpoint: 'com.atproto.repo.createRecord',
            body: [
                'repo' => $this->atp->client->sessions->session($this->atp->client->identifier)->did(),
                'collection' => 'app.bsky.feed.like',
                'record' => $record,
            ]
        );

        return StrongRef::fromResponse($response->json());
    }

    /**
     * Unlike a post (delete like record)
     */
    public function delete(string $rkey): void
    {
        $this->atp->client->post(
            endpoint: 'com.atproto.repo.deleteRecord',
            body: [
                'repo' => $this->atp->client->sessions->session($this->atp->client->identifier)->did(),
                'collection' => 'app.bsky.feed.like',
                'rkey' => $rkey,
            ]
        );
    }

    /**
     * Get a like record
     */
    public function get(string $rkey, ?string $cid = null): array
    {
        $response = $this->atp->client->get(
            endpoint: 'com.atproto.repo.getRecord',
            params: array_filter([
                'repo' => $this->atp->client->sessions->session($this->atp->client->identifier)->did(),
                'collection' => 'app.bsky.feed.like',
                'rkey' => $rkey,
                'cid' => $cid,
            ])
        );

        return $response->json('value');
    }
}
