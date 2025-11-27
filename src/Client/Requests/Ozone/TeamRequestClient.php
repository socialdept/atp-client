<?php

namespace SocialDept\AtpClient\Client\Requests\Ozone;

use SocialDept\AtpClient\Attributes\RequiresScope;
use SocialDept\AtpClient\Client\Requests\Request;
use SocialDept\AtpClient\Data\Responses\Ozone\Team\ListMembersResponse;
use SocialDept\AtpClient\Enums\Scope;

class TeamRequestClient extends Request
{
    /**
     * Get team member
     *
     * @requires transition:generic (rpc:tools.ozone.team.getMember)
     *
     * @return array<string, mixed>  Team member object
     *
     * @see https://docs.bsky.app/docs/api/tools-ozone-team-list-members
     */
    #[RequiresScope(Scope::TransitionGeneric, granular: 'rpc:tools.ozone.team.getMember')]
    public function getTeamMember(string $did): array
    {
        $response = $this->atp->client->get(
            endpoint: 'tools.ozone.team.getMember',
            params: compact('did')
        );

        return $response->json();
    }

    /**
     * List team members
     *
     * @requires transition:generic (rpc:tools.ozone.team.listMembers)
     *
     * @see https://docs.bsky.app/docs/api/tools-ozone-team-list-members
     */
    #[RequiresScope(Scope::TransitionGeneric, granular: 'rpc:tools.ozone.team.listMembers')]
    public function listTeamMembers(int $limit = 50, ?string $cursor = null): ListMembersResponse
    {
        $response = $this->atp->client->get(
            endpoint: 'tools.ozone.team.listMembers',
            params: compact('limit', 'cursor')
        );

        return ListMembersResponse::fromArray($response->json());
    }

    /**
     * Add team member
     *
     * @requires transition:generic (rpc:tools.ozone.team.addMember)
     *
     * @return array<string, mixed>  Team member object
     *
     * @see https://docs.bsky.app/docs/api/tools-ozone-team-add-member
     */
    #[RequiresScope(Scope::TransitionGeneric, granular: 'rpc:tools.ozone.team.addMember')]
    public function addTeamMember(string $did, string $role): array
    {
        $response = $this->atp->client->post(
            endpoint: 'tools.ozone.team.addMember',
            body: compact('did', 'role')
        );

        return $response->json();
    }

    /**
     * Update team member
     *
     * @requires transition:generic (rpc:tools.ozone.team.updateMember)
     *
     * @return array<string, mixed>  Team member object
     *
     * @see https://docs.bsky.app/docs/api/tools-ozone-team-update-member
     */
    #[RequiresScope(Scope::TransitionGeneric, granular: 'rpc:tools.ozone.team.updateMember')]
    public function updateTeamMember(
        string $did,
        ?bool $disabled = null,
        ?string $role = null
    ): array {
        $response = $this->atp->client->post(
            endpoint: 'tools.ozone.team.updateMember',
            body: array_filter(
                compact('did', 'disabled', 'role'),
                fn ($v) => ! is_null($v)
            )
        );

        return $response->json();
    }

    /**
     * Delete team member
     *
     * @requires transition:generic (rpc:tools.ozone.team.deleteMember)
     *
     * @see https://docs.bsky.app/docs/api/tools-ozone-team-delete-member
     */
    #[RequiresScope(Scope::TransitionGeneric, granular: 'rpc:tools.ozone.team.deleteMember')]
    public function deleteTeamMember(string $did): void
    {
        $this->atp->client->post(
            endpoint: 'tools.ozone.team.deleteMember',
            body: compact('did')
        );
    }
}
