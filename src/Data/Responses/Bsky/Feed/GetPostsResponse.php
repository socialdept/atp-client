<?php

namespace SocialDept\AtpClient\Data\Responses\Bsky\Feed;

use Illuminate\Support\Collection;
use SocialDept\AtpSchema\Generated\App\Bsky\Feed\Defs\PostView;

class GetPostsResponse
{
    /**
     * @param  Collection<int, PostView>  $posts
     */
    public function __construct(
        public readonly Collection $posts,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            posts: collect($data['posts'] ?? [])->map(
                fn (array $post) => PostView::fromArray($post)
            ),
        );
    }
}
