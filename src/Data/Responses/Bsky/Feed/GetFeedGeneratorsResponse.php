<?php

namespace SocialDept\AtpClient\Data\Responses\Bsky\Feed;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use SocialDept\AtpSchema\Generated\App\Bsky\Feed\Defs\GeneratorView;

/**
 * @implements Arrayable<string, mixed>
 */
class GetFeedGeneratorsResponse implements Arrayable
{
    /**
     * @param  Collection<int, GeneratorView>  $feeds
     */
    public function __construct(
        public readonly Collection $feeds,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            feeds: collect($data['feeds'] ?? [])->map(
                fn (array $feed) => GeneratorView::fromArray($feed)
            ),
        );
    }

    public function toArray(): array
    {
        return [
            'feeds' => $this->feeds->map(fn (GeneratorView $f) => $f->toArray())->all(),
        ];
    }
}
