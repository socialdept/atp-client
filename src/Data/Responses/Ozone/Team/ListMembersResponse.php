<?php

namespace SocialDept\AtpClient\Data\Responses\Ozone\Team;

class ListMembersResponse
{
    /**
     * @param  array<array<string, mixed>>  $members  Array of team member objects
     */
    public function __construct(
        public readonly array $members,
        public readonly ?string $cursor = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            members: $data['members'] ?? [],
            cursor: $data['cursor'] ?? null,
        );
    }
}
