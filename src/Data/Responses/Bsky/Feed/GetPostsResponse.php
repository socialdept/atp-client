<?php

namespace SocialDept\AtpClient\Data\Responses\Bsky\Feed;

use SocialDept\AtpSchema\Generated\App\Bsky\Feed\Defs\PostView;

class GetPostsResponse
{
    /**
     * @param  array<PostView>  $posts
     */
    public function __construct(
        public readonly array $posts,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            posts: array_map(
                fn (array $post) => PostView::fromArray($post),
                $data['posts'] ?? []
            ),
        );
    }
}
