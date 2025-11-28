<?php

namespace SocialDept\AtpClient\Data\Responses\Bsky\Actor;

use Illuminate\Support\Collection;
use SocialDept\AtpSchema\Generated\App\Bsky\Actor\Defs\ProfileView;

class SearchActorsResponse
{
    /**
     * @param  Collection<int, ProfileView>  $actors
     */
    public function __construct(
        public readonly Collection $actors,
        public readonly ?string $cursor = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            actors: collect($data['actors'] ?? [])->map(
                fn (array $actor) => ProfileView::fromArray($actor)
            ),
            cursor: $data['cursor'] ?? null,
        );
    }
}
