<?php

namespace SocialDept\AtpClient\Data\Responses\Chat\Convo;

use SocialDept\AtpSchema\Generated\Chat\Bsky\Convo\Defs\ConvoView;

class ListConvosResponse
{
    /**
     * @param  array<ConvoView>  $convos
     */
    public function __construct(
        public readonly array $convos,
        public readonly ?string $cursor = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            convos: array_map(
                fn (array $convo) => ConvoView::fromArray($convo),
                $data['convos'] ?? []
            ),
            cursor: $data['cursor'] ?? null,
        );
    }
}
