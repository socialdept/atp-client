<?php

namespace SocialDept\AtpClient\Data\Responses\Bsky\Actor;

use SocialDept\AtpSchema\Generated\App\Bsky\Actor\Defs\ProfileViewDetailed;

class GetProfilesResponse
{
    /**
     * @param  array<ProfileViewDetailed>  $profiles
     */
    public function __construct(
        public readonly array $profiles,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            profiles: array_map(
                fn (array $profile) => ProfileViewDetailed::fromArray($profile),
                $data['profiles'] ?? []
            ),
        );
    }
}
