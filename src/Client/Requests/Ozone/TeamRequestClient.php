<?php

namespace SocialDept\AtpClient\Client\Requests\Ozone;

use SocialDept\AtpClient\Attributes\RequiresScope;
use SocialDept\AtpClient\Client\Requests\Request;
use SocialDept\AtpClient\Enums\Scope;
use SocialDept\AtpClient\Http\Response;

class TeamRequestClient extends Request
{
    /**
     * Get team member
     *
     * @requires transition:generic (rpc:tools.ozone.team.getMember)
     *
     * @see https://docs.bsky.app/docs/api/tools-ozone-team-list-members
     */
    #[RequiresScope(Scope::TransitionGeneric, granular: 'rpc:tools.ozone.team.getMember')]
    public function getTeamMember(string $did): Response
    {
        return $this->atp->client->get(
            endpoint: 'tools.ozone.team.getMember',
            params: compact('did')
        );
    }

    /**
     * List team members
     *
     * @requires transition:generic (rpc:tools.ozone.team.listMembers)
     *
     * @see https://docs.bsky.app/docs/api/tools-ozone-team-list-members
     */
    #[RequiresScope(Scope::TransitionGeneric, granular: 'rpc:tools.ozone.team.listMembers')]
    public function listTeamMembers(int $limit = 50, ?string $cursor = null): Response
    {
        return $this->atp->client->get(
            endpoint: 'tools.ozone.team.listMembers',
            params: compact('limit', 'cursor')
        );
    }

    /**
     * Add team member
     *
     * @requires transition:generic (rpc:tools.ozone.team.addMember)
     *
     * @see https://docs.bsky.app/docs/api/tools-ozone-team-add-member
     */
    #[RequiresScope(Scope::TransitionGeneric, granular: 'rpc:tools.ozone.team.addMember')]
    public function addTeamMember(string $did, string $role): Response
    {
        return $this->atp->client->post(
            endpoint: 'tools.ozone.team.addMember',
            body: compact('did', 'role')
        );
    }

    /**
     * Update team member
     *
     * @requires transition:generic (rpc:tools.ozone.team.updateMember)
     *
     * @see https://docs.bsky.app/docs/api/tools-ozone-team-update-member
     */
    #[RequiresScope(Scope::TransitionGeneric, granular: 'rpc:tools.ozone.team.updateMember')]
    public function updateTeamMember(
        string $did,
        ?bool $disabled = null,
        ?string $role = null
    ): Response {
        return $this->atp->client->post(
            endpoint: 'tools.ozone.team.updateMember',
            body: array_filter(
                compact('did', 'disabled', 'role'),
                fn ($v) => ! is_null($v)
            )
        );
    }

    /**
     * Delete team member
     *
     * @requires transition:generic (rpc:tools.ozone.team.deleteMember)
     *
     * @see https://docs.bsky.app/docs/api/tools-ozone-team-delete-member
     */
    #[RequiresScope(Scope::TransitionGeneric, granular: 'rpc:tools.ozone.team.deleteMember')]
    public function deleteTeamMember(string $did): Response
    {
        return $this->atp->client->post(
            endpoint: 'tools.ozone.team.deleteMember',
            body: compact('did')
        );
    }
}
