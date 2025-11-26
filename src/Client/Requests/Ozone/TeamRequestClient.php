<?php

namespace SocialDept\AtpClient\Client\Requests\Ozone;

use SocialDept\AtpClient\Client\Requests\Request;
use SocialDept\AtpClient\Http\Response;

class TeamRequestClient extends Request
{
    /**
     * Get team member
     *
     * @see https://docs.bsky.app/docs/api/tools-ozone-team-list-members
     */
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
     * @see https://docs.bsky.app/docs/api/tools-ozone-team-list-members
     */
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
     * @see https://docs.bsky.app/docs/api/tools-ozone-team-add-member
     */
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
     * @see https://docs.bsky.app/docs/api/tools-ozone-team-update-member
     */
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
     * @see https://docs.bsky.app/docs/api/tools-ozone-team-delete-member
     */
    public function deleteTeamMember(string $did): Response
    {
        return $this->atp->client->post(
            endpoint: 'tools.ozone.team.deleteMember',
            body: compact('did')
        );
    }
}
