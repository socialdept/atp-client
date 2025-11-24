<?php

namespace SocialDept\AtpClient\Client;

use Illuminate\Http\Client\Factory;
use SocialDept\AtpClient\Auth\DPoPNonceManager;
use SocialDept\AtpClient\Http\HasHttp;
use SocialDept\AtpClient\Http\Response;
use SocialDept\AtpClient\Session\SessionManager;

class OzoneClient
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
     * Get moderation event
     */
    public function getModerationEvent(int $id): Response
    {
        return $this->get('tools.ozone.moderation.getEvent', compact('id'));
    }

    /**
     * Get moderation events
     */
    public function getModerationEvents(?string $subject = null, ?array $types = null, ?string $createdBy = null, int $limit = 50, ?string $cursor = null): Response
    {
        return $this->get('tools.ozone.moderation.getEvents', array_filter(compact('subject', 'types', 'createdBy', 'limit', 'cursor'), fn ($v) => ! is_null($v)));
    }

    /**
     * Get record
     */
    public function getRecord(string $uri, ?string $cid = null): Response
    {
        return $this->get('tools.ozone.moderation.getRecord', compact('uri', 'cid'));
    }

    /**
     * Get repo
     */
    public function getRepo(string $did): Response
    {
        return $this->get('tools.ozone.moderation.getRepo', compact('did'));
    }

    /**
     * Query events
     */
    public function queryEvents(?array $types = null, ?string $createdBy = null, ?string $subject = null, int $limit = 50, ?string $cursor = null, bool $sortDirection = false): Response
    {
        return $this->get('tools.ozone.moderation.queryEvents', array_filter(compact('types', 'createdBy', 'subject', 'limit', 'cursor', 'sortDirection'), fn ($v) => ! is_null($v)));
    }

    /**
     * Query statuses
     */
    public function queryStatuses(?string $subject = null, ?array $tags = null, ?string $excludeTags = null, int $limit = 50, ?string $cursor = null): Response
    {
        return $this->get('tools.ozone.moderation.queryStatuses', array_filter(compact('subject', 'tags', 'excludeTags', 'limit', 'cursor'), fn ($v) => ! is_null($v)));
    }

    /**
     * Search repos
     */
    public function searchRepos(?string $term = null, ?string $invitedBy = null, int $limit = 50, ?string $cursor = null): Response
    {
        return $this->get('tools.ozone.moderation.searchRepos', array_filter(compact('term', 'invitedBy', 'limit', 'cursor'), fn ($v) => ! is_null($v)));
    }

    /**
     * Emit moderation event
     */
    public function emitEvent(array $event, string $subject, array $subjectBlobCids = [], ?string $createdBy = null): Response
    {
        return $this->post('tools.ozone.moderation.emitEvent', compact('event', 'subject', 'subjectBlobCids', 'createdBy'));
    }

    /**
     * Get blob
     */
    public function getBlob(string $did, string $cid): Response
    {
        return $this->get('tools.ozone.server.getBlob', compact('did', 'cid'));
    }

    /**
     * Get config
     */
    public function getConfig(): Response
    {
        return $this->get('tools.ozone.server.getConfig');
    }

    /**
     * Get team member
     */
    public function getTeamMember(string $did): Response
    {
        return $this->get('tools.ozone.team.getMember', compact('did'));
    }

    /**
     * List team members
     */
    public function listTeamMembers(int $limit = 50, ?string $cursor = null): Response
    {
        return $this->get('tools.ozone.team.listMembers', compact('limit', 'cursor'));
    }

    /**
     * Add team member
     */
    public function addTeamMember(string $did, string $role): Response
    {
        return $this->post('tools.ozone.team.addMember', compact('did', 'role'));
    }

    /**
     * Update team member
     */
    public function updateTeamMember(string $did, ?bool $disabled = null, ?string $role = null): Response
    {
        return $this->post('tools.ozone.team.updateMember', array_filter(compact('did', 'disabled', 'role'), fn ($v) => ! is_null($v)));
    }

    /**
     * Delete team member
     */
    public function deleteTeamMember(string $did): Response
    {
        return $this->post('tools.ozone.team.deleteMember', compact('did'));
    }
}
