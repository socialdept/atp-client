<?php

namespace SocialDept\AtpClient\Data\Responses\Bsky\Feed;

use Illuminate\Support\Collection;
use SocialDept\AtpSchema\Generated\App\Bsky\Feed\Defs\GeneratorView;

class GetActorFeedsResponse
{
    /**
     * @param  Collection<int, GeneratorView>  $feeds
     */
    public function __construct(
        public readonly Collection $feeds,
        public readonly ?string $cursor = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            feeds: collect($data['feeds'] ?? [])->map(
                fn (array $feed) => GeneratorView::fromArray($feed)
            ),
            cursor: $data['cursor'] ?? null,
        );
    }
}
