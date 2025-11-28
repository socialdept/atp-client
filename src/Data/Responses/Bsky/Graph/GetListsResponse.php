<?php

namespace SocialDept\AtpClient\Data\Responses\Bsky\Graph;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use SocialDept\AtpSchema\Generated\App\Bsky\Graph\Defs\ListView;

/**
 * @implements Arrayable<string, mixed>
 */
class GetListsResponse implements Arrayable
{
    /**
     * @param  Collection<int, ListView>  $lists
     */
    public function __construct(
        public readonly Collection $lists,
        public readonly ?string $cursor = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            lists: collect($data['lists'] ?? [])->map(
                fn (array $list) => ListView::fromArray($list)
            ),
            cursor: $data['cursor'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'lists' => $this->lists->map(fn (ListView $l) => $l->toArray())->all(),
            'cursor' => $this->cursor,
        ];
    }
}
