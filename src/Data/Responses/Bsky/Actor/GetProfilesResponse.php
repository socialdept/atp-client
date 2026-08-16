<?php

namespace SocialDept\AtpClient\Data\Responses\Bsky\Actor;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use SocialDept\AtpSchema\Generated\App\Bsky\Actor\Defs\ProfileViewDetailed;

/**
 * @implements Arrayable<string, mixed>
 */
class GetProfilesResponse implements Arrayable
{
    /**
     * @param  Collection<int, ProfileViewDetailed>  $profiles
     */
    public function __construct(
        public readonly Collection $profiles,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            profiles: collect($data['profiles'] ?? [])->map(
                fn (array $profile) => ProfileViewDetailed::fromArray($profile)
            ),
        );
    }

    public function toArray(): array
    {
        return [
            'profiles' => $this->profiles->map(fn (ProfileViewDetailed $p) => $p->toArray())->all(),
        ];
    }
}
