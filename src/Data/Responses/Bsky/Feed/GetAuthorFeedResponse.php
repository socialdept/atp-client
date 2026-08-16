<?php

namespace SocialDept\AtpClient\Data\Responses\Bsky\Feed;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use SocialDept\AtpSchema\Generated\App\Bsky\Feed\Defs\FeedViewPost;

/**
 * @implements Arrayable<string, mixed>
 */
class GetAuthorFeedResponse implements Arrayable
{
    /**
     * @param  Collection<int, FeedViewPost>  $feed
     */
    public function __construct(
        public readonly Collection $feed,
        public readonly ?string $cursor = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            feed: collect($data['feed'] ?? [])->map(
                fn (array $post) => FeedViewPost::fromArray($post)
            ),
            cursor: $data['cursor'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'feed' => $this->feed->map(fn (FeedViewPost $p) => $p->toArray())->all(),
            'cursor' => $this->cursor,
        ];
    }
}
