<?php

namespace SocialDept\AtpClient\Data\Responses\Bsky\Graph;

use Illuminate\Support\Collection;
use SocialDept\AtpSchema\Generated\App\Bsky\Graph\Defs\ListView;

class GetListsResponse
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
}
