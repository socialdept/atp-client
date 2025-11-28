<?php

namespace SocialDept\AtpClient\Data\Responses\Bsky\Feed;

use Illuminate\Support\Collection;
use SocialDept\AtpSchema\Generated\App\Bsky\Feed\Defs\PostView;

class SearchPostsResponse
{
    /**
     * @param  Collection<int, PostView>  $posts
     */
    public function __construct(
        public readonly Collection $posts,
        public readonly ?string $cursor = null,
        public readonly ?int $hitsTotal = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            posts: collect($data['posts'] ?? [])->map(
                fn (array $post) => PostView::fromArray($post)
            ),
            cursor: $data['cursor'] ?? null,
            hitsTotal: $data['hitsTotal'] ?? null,
        );
    }
}
