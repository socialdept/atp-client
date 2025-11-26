<?php

namespace SocialDept\AtpClient\Client\Requests\Ozone;

use SocialDept\AtpClient\Attributes\RequiresScope;
use SocialDept\AtpClient\Client\Requests\Request;
use SocialDept\AtpClient\Enums\Scope;
use SocialDept\AtpClient\Http\Response;

class ModerationRequestClient extends Request
{
    /**
     * Get moderation event
     *
     * @requires transition:generic (rpc:tools.ozone.moderation.getEvent)
     *
     * @see https://docs.bsky.app/docs/api/tools-ozone-moderation-get-event
     */
    #[RequiresScope(Scope::TransitionGeneric, granular: 'rpc:tools.ozone.moderation.getEvent')]
    public function getModerationEvent(int $id): Response
    {
        return $this->atp->client->get(
            endpoint: 'tools.ozone.moderation.getEvent',
            params: compact('id')
        );
    }

    /**
     * Get moderation events
     *
     * @requires transition:generic (rpc:tools.ozone.moderation.getEvents)
     *
     * @see https://docs.bsky.app/docs/api/tools-ozone-moderation-query-events
     */
    #[RequiresScope(Scope::TransitionGeneric, granular: 'rpc:tools.ozone.moderation.getEvents')]
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
     *
     * @requires transition:generic (rpc:tools.ozone.moderation.getRecord)
     *
     * @see https://docs.bsky.app/docs/api/tools-ozone-moderation-get-record
     */
    #[RequiresScope(Scope::TransitionGeneric, granular: 'rpc:tools.ozone.moderation.getRecord')]
    public function getRecord(string $uri, ?string $cid = null): Response
    {
        return $this->atp->client->get(
            endpoint: 'tools.ozone.moderation.getRecord',
            params: compact('uri', 'cid')
        );
    }

    /**
     * Get repo
     *
     * @requires transition:generic (rpc:tools.ozone.moderation.getRepo)
     *
     * @see https://docs.bsky.app/docs/api/tools-ozone-moderation-get-repo
     */
    #[RequiresScope(Scope::TransitionGeneric, granular: 'rpc:tools.ozone.moderation.getRepo')]
    public function getRepo(string $did): Response
    {
        return $this->atp->client->get(
            endpoint: 'tools.ozone.moderation.getRepo',
            params: compact('did')
        );
    }

    /**
     * Query events
     *
     * @requires transition:generic (rpc:tools.ozone.moderation.queryEvents)
     *
     * @see https://docs.bsky.app/docs/api/tools-ozone-moderation-query-events
     */
    #[RequiresScope(Scope::TransitionGeneric, granular: 'rpc:tools.ozone.moderation.queryEvents')]
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
     *
     * @requires transition:generic (rpc:tools.ozone.moderation.queryStatuses)
     *
     * @see https://docs.bsky.app/docs/api/tools-ozone-moderation-query-statuses
     */
    #[RequiresScope(Scope::TransitionGeneric, granular: 'rpc:tools.ozone.moderation.queryStatuses')]
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
     *
     * @requires transition:generic (rpc:tools.ozone.moderation.searchRepos)
     *
     * @see https://docs.bsky.app/docs/api/tools-ozone-moderation-search-repos
     */
    #[RequiresScope(Scope::TransitionGeneric, granular: 'rpc:tools.ozone.moderation.searchRepos')]
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
     *
     * @requires transition:generic (rpc:tools.ozone.moderation.emitEvent)
     *
     * @see https://docs.bsky.app/docs/api/tools-ozone-moderation-emit-event
     */
    #[RequiresScope(Scope::TransitionGeneric, granular: 'rpc:tools.ozone.moderation.emitEvent')]
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
