<?php

namespace SocialDept\AtpClient\Client\Requests\Ozone;

use SocialDept\AtpClient\Client\Requests\Request;
use SocialDept\AtpClient\Http\Response;

class ModerationRequestClient extends Request
{
    /**
     * Get moderation event
     */
    public function getModerationEvent(int $id): Response
    {
        return $this->atp->client->get(
            endpoint: 'tools.ozone.moderation.getEvent',
            params: compact('id')
        );
    }

    /**
     * Get moderation events
     */
    public function getModerationEvents(
        ?string $subject = null,
        ?array $types = null,
        ?string $createdBy = null,
        int $limit = 50,
        ?string $cursor = null
    ): Response {
        return $this->atp->client->get(
            endpoint: 'tools.ozone.moderation.getEvents',
            params: array_filter(
                compact('subject', 'types', 'createdBy', 'limit', 'cursor'),
                fn ($v) => ! is_null($v)
            )
        );
    }

    /**
     * Get record
     */
    public function getRecord(string $uri, ?string $cid = null): Response
    {
        return $this->atp->client->get(
            endpoint: 'tools.ozone.moderation.getRecord',
            params: compact('uri', 'cid')
        );
    }

    /**
     * Get repo
     */
    public function getRepo(string $did): Response
    {
        return $this->atp->client->get(
            endpoint: 'tools.ozone.moderation.getRepo',
            params: compact('did')
        );
    }

    /**
     * Query events
     */
    public function queryEvents(
        ?array $types = null,
        ?string $createdBy = null,
        ?string $subject = null,
        int $limit = 50,
        ?string $cursor = null,
        bool $sortDirection = false
    ): Response {
        return $this->atp->client->get(
            endpoint: 'tools.ozone.moderation.queryEvents',
            params: array_filter(
                compact('types', 'createdBy', 'subject', 'limit', 'cursor', 'sortDirection'),
                fn ($v) => ! is_null($v)
            )
        );
    }

    /**
     * Query statuses
     */
    public function queryStatuses(
        ?string $subject = null,
        ?array $tags = null,
        ?string $excludeTags = null,
        int $limit = 50,
        ?string $cursor = null
    ): Response {
        return $this->atp->client->get(
            endpoint: 'tools.ozone.moderation.queryStatuses',
            params: array_filter(
                compact('subject', 'tags', 'excludeTags', 'limit', 'cursor'),
                fn ($v) => ! is_null($v)
            )
        );
    }

    /**
     * Search repos
     */
    public function searchRepos(
        ?string $term = null,
        ?string $invitedBy = null,
        int $limit = 50,
        ?string $cursor = null
    ): Response {
        return $this->atp->client->get(
            endpoint: 'tools.ozone.moderation.searchRepos',
            params: array_filter(
                compact('term', 'invitedBy', 'limit', 'cursor'),
                fn ($v) => ! is_null($v)
            )
        );
    }

    /**
     * Emit moderation event
     */
    public function emitEvent(
        array $event,
        string $subject,
        array $subjectBlobCids = [],
        ?string $createdBy = null
    ): Response {
        return $this->atp->client->post(
            endpoint: 'tools.ozone.moderation.emitEvent',
            body: compact('event', 'subject', 'subjectBlobCids', 'createdBy')
        );
    }
}
