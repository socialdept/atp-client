<?php

namespace SocialDept\AtpClient\Data\Responses\Bsky\Feed;

use Illuminate\Support\Collection;
use SocialDept\AtpSchema\Generated\App\Bsky\Feed\Defs\FeedViewPost;

class GetFeedResponse
{
    /**
     * @param  Collection<int, FeedViewPost>  $feed
     */
    public function __construct(
        public readonly Collection $feed,
        public readonly ?string $cursor = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            feed: collect($data['feed'] ?? [])->map(
                fn (array $post) => FeedViewPost::fromArray($post)
            ),
            cursor: $data['cursor'] ?? null,
        );
    }
}
