<?php

namespace SocialDept\AtpClient\Data\Responses\Ozone\Team;

use Illuminate\Support\Collection;

class ListMembersResponse
{
    /**
     * @param  Collection<int, array<string, mixed>>  $members  Collection of team member objects
     */
    public function __construct(
        public readonly Collection $members,
        public readonly ?string $cursor = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            members: collect($data['members'] ?? []),
            cursor: $data['cursor'] ?? null,
        );
    }
}
