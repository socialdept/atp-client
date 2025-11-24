<?php

namespace SocialDept\AtpClient\Client\Records;

use DateTimeInterface;
use SocialDept\AtpClient\Client\Requests\Request;
use SocialDept\AtpClient\Data\StrongRef;

class FollowRecordClient extends Request
{
    /**
     * Follow a user
     */
    public function create(
        string $subject,
        ?DateTimeInterface $createdAt = null
    ): StrongRef {
        $record = [
            '$type' => 'app.bsky.graph.follow',
            'subject' => $subject, // DID
            'createdAt' => ($createdAt ?? now())->format('c'),
        ];

        $response = $this->atp->client->post(
            endpoint: 'com.atproto.repo.createRecord',
            body: [
                'repo' => $this->atp->client->sessions->session($this->atp->client->identifier)->did(),
                'collection' => 'app.bsky.graph.follow',
                'record' => $record,
            ]
        );

        return StrongRef::fromResponse($response->json());
    }

    /**
     * Unfollow a user (delete follow record)
     */
    public function delete(string $rkey): void
    {
        $this->atp->client->post(
            endpoint: 'com.atproto.repo.deleteRecord',
            body: [
                'repo' => $this->atp->client->sessions->session($this->atp->client->identifier)->did(),
                'collection' => 'app.bsky.graph.follow',
                'rkey' => $rkey,
            ]
        );
    }

    /**
     * Get a follow record
     */
    public function get(string $rkey, ?string $cid = null): array
    {
        $response = $this->atp->client->get(
            endpoint: 'com.atproto.repo.getRecord',
            params: array_filter([
                'repo' => $this->atp->client->sessions->session($this->atp->client->identifier)->did(),
                'collection' => 'app.bsky.graph.follow',
                'rkey' => $rkey,
                'cid' => $cid,
            ])
        );

        return $response->json('value');
    }
}
