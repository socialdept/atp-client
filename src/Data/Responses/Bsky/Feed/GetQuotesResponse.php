<?php

namespace SocialDept\AtpClient\Data\Responses\Bsky\Feed;

use SocialDept\AtpSchema\Generated\App\Bsky\Feed\Defs\PostView;

class GetQuotesResponse
{
    /**
     * @param  array<PostView>  $posts
     */
    public function __construct(
        public readonly string $uri,
        public readonly array $posts,
        public readonly ?string $cid = null,
        public readonly ?string $cursor = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            uri: $data['uri'],
            posts: array_map(
                fn (array $post) => PostView::fromArray($post),
                $data['posts'] ?? []
            ),
            cid: $data['cid'] ?? null,
            cursor: $data['cursor'] ?? null,
        );
    }
}
