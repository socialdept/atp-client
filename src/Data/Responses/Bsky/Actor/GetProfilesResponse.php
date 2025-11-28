<?php

namespace SocialDept\AtpClient\Data\Responses\Bsky\Actor;

use Illuminate\Support\Collection;
use SocialDept\AtpSchema\Generated\App\Bsky\Actor\Defs\ProfileViewDetailed;

class GetProfilesResponse
{
    /**
     * @param  Collection<int, ProfileViewDetailed>  $profiles
     */
    public function __construct(
        public readonly Collection $profiles,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            profiles: collect($data['profiles'] ?? [])->map(
                fn (array $profile) => ProfileViewDetailed::fromArray($profile)
            ),
        );
    }
}
