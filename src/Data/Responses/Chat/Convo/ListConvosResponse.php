<?php

namespace SocialDept\AtpClient\Data\Responses\Chat\Convo;

use Illuminate\Support\Collection;
use SocialDept\AtpSchema\Generated\Chat\Bsky\Convo\Defs\ConvoView;

class ListConvosResponse
{
    /**
     * @param  Collection<int, ConvoView>  $convos
     */
    public function __construct(
        public readonly Collection $convos,
        public readonly ?string $cursor = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            convos: collect($data['convos'] ?? [])->map(
                fn (array $convo) => ConvoView::fromArray($convo)
            ),
            cursor: $data['cursor'] ?? null,
        );
    }
}
