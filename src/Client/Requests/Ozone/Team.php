<?php

namespace SocialDept\AtpClient\Client\Requests\Ozone;

use SocialDept\AtpClient\Client\Requests\Request;
use SocialDept\AtpClient\Http\Response;

class Team extends Request
{
    /**
     * Get team member
     */
    public function getTeamMember(string $did): Response
    {
        return $this->atp->client->get('tools.ozone.team.getMember', compact('did'));
    }

    /**
     * List team members
     */
    public function listTeamMembers(int $limit = 50, ?string $cursor = null): Response
    {
        return $this->atp->client->get('tools.ozone.team.listMembers', compact('limit', 'cursor'));
    }

    /**
     * Add team member
     */
    public function addTeamMember(string $did, string $role): Response
    {
        return $this->atp->client->post('tools.ozone.team.addMember', compact('did', 'role'));
    }

    /**
     * Update team member
     */
    public function updateTeamMember(string $did, ?bool $disabled = null, ?string $role = null): Response
    {
        return $this->atp->client->post('tools.ozone.team.updateMember', array_filter(compact('did', 'disabled', 'role'), fn ($v) => ! is_null($v)));
    }

    /**
     * Delete team member
     */
    public function deleteTeamMember(string $did): Response
    {
        return $this->atp->client->post('tools.ozone.team.deleteMember', compact('did'));
    }
}
