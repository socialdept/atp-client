<?php

namespace SocialDept\AtpClient\Data\Responses\Bsky\Graph;

use Illuminate\Support\Collection;
use SocialDept\AtpSchema\Generated\App\Bsky\Graph\Defs\ListItemView;
use SocialDept\AtpSchema\Generated\App\Bsky\Graph\Defs\ListView;

class GetListResponse
{
    /**
     * @param  Collection<int, ListItemView>  $items
     */
    public function __construct(
        public readonly ListView $list,
        public readonly Collection $items,
        public readonly ?string $cursor = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            list: ListView::fromArray($data['list']),
            items: collect($data['items'] ?? [])->map(
                fn (array $item) => ListItemView::fromArray($item)
            ),
            cursor: $data['cursor'] ?? null,
        );
    }
}
