<?php

namespace SocialDept\AtpClient\Data\Responses\Bsky\Graph;

use SocialDept\AtpSchema\Generated\App\Bsky\Graph\Defs\ListItemView;
use SocialDept\AtpSchema\Generated\App\Bsky\Graph\Defs\ListView;

class GetListResponse
{
    /**
     * @param  array<ListItemView>  $items
     */
    public function __construct(
        public readonly ListView $list,
        public readonly array $items,
        public readonly ?string $cursor = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            list: ListView::fromArray($data['list']),
            items: array_map(
                fn (array $item) => ListItemView::fromArray($item),
                $data['items'] ?? []
            ),
            cursor: $data['cursor'] ?? null,
        );
    }
}
