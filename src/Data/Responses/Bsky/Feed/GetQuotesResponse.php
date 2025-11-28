<?php

namespace SocialDept\AtpClient\Data\Responses\Bsky\Feed;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use SocialDept\AtpSchema\Generated\App\Bsky\Feed\Defs\PostView;

/**
 * @implements Arrayable<string, mixed>
 */
class GetQuotesResponse implements Arrayable
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

    public function toArray(): array
    {
        return [
            'uri' => $this->uri,
            'posts' => $this->posts->map(fn (PostView $p) => $p->toArray())->all(),
            'cid' => $this->cid,
            'cursor' => $this->cursor,
        ];
    }
}
