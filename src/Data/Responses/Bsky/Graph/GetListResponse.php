<?php

namespace SocialDept\AtpClient\Data\Responses\Bsky\Graph;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use SocialDept\AtpSchema\Generated\App\Bsky\Graph\Defs\ListItemView;
use SocialDept\AtpSchema\Generated\App\Bsky\Graph\Defs\ListView;

/**
 * @implements Arrayable<string, mixed>
 */
class GetListResponse implements Arrayable
{
    /**
     * @param  Collection<int, ListItemView>  $items
     */
    public function __construct(
        public readonly ListView $list,
        public readonly Collection $items,
        public readonly ?string $cursor = null,
    ) {
    }

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

    public function toArray(): array
    {
        return [
            'list' => $this->list->toArray(),
            'items' => $this->items->map(fn (ListItemView $i) => $i->toArray())->all(),
            'cursor' => $this->cursor,
        ];
    }
}
