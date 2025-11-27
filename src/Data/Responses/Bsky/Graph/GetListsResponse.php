<?php

namespace SocialDept\AtpClient\Data\Responses\Bsky\Graph;

use SocialDept\AtpSchema\Generated\App\Bsky\Graph\Defs\ListView;

class GetListsResponse
{
    /**
     * @param  array<ListView>  $lists
     */
    public function __construct(
        public readonly array $lists,
        public readonly ?string $cursor = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            lists: array_map(
                fn (array $list) => ListView::fromArray($list),
                $data['lists'] ?? []
            ),
            cursor: $data['cursor'] ?? null,
        );
    }
}
