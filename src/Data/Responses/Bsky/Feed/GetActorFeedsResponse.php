<?php

namespace SocialDept\AtpClient\Data\Responses\Bsky\Feed;

use SocialDept\AtpSchema\Generated\App\Bsky\Feed\Defs\GeneratorView;

class GetActorFeedsResponse
{
    /**
     * @param  array<GeneratorView>  $feeds
     */
    public function __construct(
        public readonly array $feeds,
        public readonly ?string $cursor = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            feeds: array_map(
                fn (array $feed) => GeneratorView::fromArray($feed),
                $data['feeds'] ?? []
            ),
            cursor: $data['cursor'] ?? null,
        );
    }
}
