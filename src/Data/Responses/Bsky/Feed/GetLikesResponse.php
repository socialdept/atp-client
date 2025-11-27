<?php

namespace SocialDept\AtpClient\Data\Responses\Bsky\Feed;

use SocialDept\AtpSchema\Generated\App\Bsky\Feed\GetLikes\Like;

class GetLikesResponse
{
    /**
     * @param  array<Like>  $likes
     */
    public function __construct(
        public readonly string $uri,
        public readonly array $likes,
        public readonly ?string $cid = null,
        public readonly ?string $cursor = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            uri: $data['uri'],
            likes: array_map(
                fn (array $like) => Like::fromArray($like),
                $data['likes'] ?? []
            ),
            cid: $data['cid'] ?? null,
            cursor: $data['cursor'] ?? null,
        );
    }
}
