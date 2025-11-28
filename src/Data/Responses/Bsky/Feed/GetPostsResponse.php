<?php

namespace SocialDept\AtpClient\Data\Responses\Bsky\Feed;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use SocialDept\AtpSchema\Generated\App\Bsky\Feed\Defs\PostView;

/**
 * @implements Arrayable<string, mixed>
 */
class GetPostsResponse implements Arrayable
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

    public function toArray(): array
    {
        return [
            'posts' => $this->posts->map(fn (PostView $p) => $p->toArray())->all(),
        ];
    }
}
