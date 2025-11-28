<?php

namespace SocialDept\AtpClient\Data\Responses\Bsky\Feed;

use Illuminate\Support\Collection;
use SocialDept\AtpSchema\Generated\App\Bsky\Feed\Defs\PostView;

class GetQuotesResponse
{
    /**
     * @param  Collection<int, PostView>  $posts
     */
    public function __construct(
        public readonly string $uri,
        public readonly Collection $posts,
        public readonly ?string $cid = null,
        public readonly ?string $cursor = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            uri: $data['uri'],
            posts: collect($data['posts'] ?? [])->map(
                fn (array $post) => PostView::fromArray($post)
            ),
            cid: $data['cid'] ?? null,
            cursor: $data['cursor'] ?? null,
        );
    }
}
