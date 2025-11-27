<?php

namespace SocialDept\AtpClient\Data\Responses\Bsky\Actor;

use SocialDept\AtpSchema\Generated\App\Bsky\Actor\Defs\ProfileViewBasic;

class SearchActorsTypeaheadResponse
{
    /**
     * @param  array<ProfileViewBasic>  $actors
     */
    public function __construct(
        public readonly array $actors,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            actors: array_map(
                fn (array $actor) => ProfileViewBasic::fromArray($actor),
                $data['actors'] ?? []
            ),
        );
    }
}
