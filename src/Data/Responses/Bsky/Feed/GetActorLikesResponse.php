<?php

namespace SocialDept\AtpClient\Data\Responses\Bsky\Feed;

use SocialDept\AtpSchema\Generated\App\Bsky\Feed\Defs\FeedViewPost;

class GetActorLikesResponse
{
    /**
     * @param  array<FeedViewPost>  $feed
     */
    public function __construct(
        public readonly array $feed,
        public readonly ?string $cursor = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            feed: array_map(
                fn (array $post) => FeedViewPost::fromArray($post),
                $data['feed'] ?? []
            ),
            cursor: $data['cursor'] ?? null,
        );
    }
}
