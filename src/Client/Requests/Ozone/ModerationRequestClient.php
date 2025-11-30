<?php

namespace SocialDept\AtpClient\Client\Requests\Ozone;

use SocialDept\AtpClient\Attributes\ScopedEndpoint;
use SocialDept\AtpClient\Client\Requests\Request;
use SocialDept\AtpClient\Data\Responses\Ozone\Moderation\QueryEventsResponse;
use SocialDept\AtpClient\Data\Responses\Ozone\Moderation\QueryStatusesResponse;
use SocialDept\AtpClient\Data\Responses\Ozone\Moderation\SearchReposResponse;
use SocialDept\AtpClient\Enums\Nsid\OzoneModeration;
use SocialDept\AtpClient\Enums\Scope;
use SocialDept\AtpClient\Http\Response;
use SocialDept\AtpSchema\Generated\Tools\Ozone\Moderation\Defs\ModEventView;
use SocialDept\AtpSchema\Generated\Tools\Ozone\Moderation\Defs\ModEventViewDetail;
use SocialDept\AtpSchema\Generated\Tools\Ozone\Moderation\Defs\RecordViewDetail;
use SocialDept\AtpSchema\Generated\Tools\Ozone\Moderation\Defs\RepoViewDetail;

class ModerationRequestClient extends Request
{
    /**
     * Get moderation event
     *
     * @requires transition:generic (rpc:tools.ozone.moderation.getEvent)
     *
     * @see https://docs.bsky.app/docs/api/tools-ozone-moderation-get-event
     */
    #[ScopedEndpoint(Scope::TransitionGeneric, granular: 'rpc:tools.ozone.moderation.getEvent')]
    public function getModerationEvent(int $id): ModEventViewDetail
    {
        $response = $this->atp->client->get(
            endpoint: OzoneModeration::GetEvent,
            params: compact('id')
        );

        return ModEventViewDetail::fromArray($response->json());
    }

    /**
     * Get moderation events
     *
     * @requires transition:generic (rpc:tools.ozone.moderation.getEvents)
     *
     * @see https://docs.bsky.app/docs/api/tools-ozone-moderation-query-events
     */
    #[ScopedEndpoint(Scope::TransitionGeneric, granular: 'rpc:tools.ozone.moderation.getEvents')]
    public function getModerationEvents(
        ?string $subject = null,
        ?array $types = null,
        ?string $createdBy = null,
        int $limit = 50,
        ?string $cursor = null
    ): Response {
        return $this->atp->client->get(
            endpoint: OzoneModeration::GetEvents,
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
    #[ScopedEndpoint(Scope::TransitionGeneric, granular: 'rpc:tools.ozone.moderation.getRecord')]
    public function getRecord(string $uri, ?string $cid = null): RecordViewDetail
    {
        $response = $this->atp->client->get(
            endpoint: OzoneModeration::GetRecord,
            params: compact('uri', 'cid')
        );

        return RecordViewDetail::fromArray($response->json());
    }

    /**
     * Get repo
     *
     * @requires transition:generic (rpc:tools.ozone.moderation.getRepo)
     *
     * @see https://docs.bsky.app/docs/api/tools-ozone-moderation-get-repo
     */
    #[ScopedEndpoint(Scope::TransitionGeneric, granular: 'rpc:tools.ozone.moderation.getRepo')]
    public function getRepo(string $did): RepoViewDetail
    {
        $response = $this->atp->client->get(
            endpoint: OzoneModeration::GetRepo,
            params: compact('did')
        );

        return RepoViewDetail::fromArray($response->json());
    }

    /**
     * Query events
     *
     * @requires transition:generic (rpc:tools.ozone.moderation.queryEvents)
     *
     * @see https://docs.bsky.app/docs/api/tools-ozone-moderation-query-events
     */
    #[ScopedEndpoint(Scope::TransitionGeneric, granular: 'rpc:tools.ozone.moderation.queryEvents')]
    public function queryEvents(
        ?array $types = null,
        ?string $createdBy = null,
        ?string $subject = null,
        int $limit = 50,
        ?string $cursor = null,
        bool $sortDirection = false
    ): QueryEventsResponse {
        $response = $this->atp->client->get(
            endpoint: OzoneModeration::QueryEvents,
            params: array_filter(
                compact('types', 'createdBy', 'subject', 'limit', 'cursor', 'sortDirection'),
                fn ($v) => ! is_null($v)
            )
        );

        return QueryEventsResponse::fromArray($response->json());
    }

    /**
     * Query statuses
     *
     * @requires transition:generic (rpc:tools.ozone.moderation.queryStatuses)
     *
     * @see https://docs.bsky.app/docs/api/tools-ozone-moderation-query-statuses
     */
    #[ScopedEndpoint(Scope::TransitionGeneric, granular: 'rpc:tools.ozone.moderation.queryStatuses')]
    public function queryStatuses(
        ?string $subject = null,
        ?array $tags = null,
        ?string $excludeTags = null,
        int $limit = 50,
        ?string $cursor = null
    ): QueryStatusesResponse {
        $response = $this->atp->client->get(
            endpoint: OzoneModeration::QueryStatuses,
            params: array_filter(
                compact('subject', 'tags', 'excludeTags', 'limit', 'cursor'),
                fn ($v) => ! is_null($v)
            )
        );

        return QueryStatusesResponse::fromArray($response->json());
    }

    /**
     * Search repos
     *
     * @requires transition:generic (rpc:tools.ozone.moderation.searchRepos)
     *
     * @see https://docs.bsky.app/docs/api/tools-ozone-moderation-search-repos
     */
    #[ScopedEndpoint(Scope::TransitionGeneric, granular: 'rpc:tools.ozone.moderation.searchRepos')]
    public function searchRepos(
        ?string $term = null,
        ?string $invitedBy = null,
        int $limit = 50,
        ?string $cursor = null
    ): SearchReposResponse {
        $response = $this->atp->client->get(
            endpoint: OzoneModeration::SearchRepos,
            params: array_filter(
                compact('term', 'invitedBy', 'limit', 'cursor'),
                fn ($v) => ! is_null($v)
            )
        );

        return SearchReposResponse::fromArray($response->json());
    }

    /**
     * Emit moderation event
     *
     * @requires transition:generic (rpc:tools.ozone.moderation.emitEvent)
     *
     * @see https://docs.bsky.app/docs/api/tools-ozone-moderation-emit-event
     */
    #[ScopedEndpoint(Scope::TransitionGeneric, granular: 'rpc:tools.ozone.moderation.emitEvent')]
    public function emitEvent(
        array $event,
        string $subject,
        array $subjectBlobCids = [],
        ?string $createdBy = null
    ): ModEventView {
        $response = $this->atp->client->post(
            endpoint: OzoneModeration::EmitEvent,
            body: compact('event', 'subject', 'subjectBlobCids', 'createdBy')
        );

        return ModEventView::fromArray($response->json());
    }
}
