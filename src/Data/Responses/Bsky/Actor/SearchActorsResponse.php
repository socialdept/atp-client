<?php

namespace SocialDept\AtpClient\Data\Responses\Bsky\Actor;

use SocialDept\AtpSchema\Generated\App\Bsky\Actor\Defs\ProfileView;

class SearchActorsResponse
{
    /**
     * @param  array<ProfileView>  $actors
     */
    public function __construct(
        public readonly array $actors,
        public readonly ?string $cursor = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            actors: array_map(
                fn (array $actor) => ProfileView::fromArray($actor),
                $data['actors'] ?? []
            ),
            cursor: $data['cursor'] ?? null,
        );
    }
}
