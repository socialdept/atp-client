<?php

namespace SocialDept\AtpClient\Data\Responses\Bsky\Feed;

use Illuminate\Support\Collection;
use SocialDept\AtpSchema\Generated\App\Bsky\Feed\GetLikes\Like;

class GetLikesResponse
{
    /**
     * @param  Collection<int, Like>  $likes
     */
    public function __construct(
        public readonly string $uri,
        public readonly Collection $likes,
        public readonly ?string $cid = null,
        public readonly ?string $cursor = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            uri: $data['uri'],
            likes: collect($data['likes'] ?? [])->map(
                fn (array $like) => Like::fromArray($like)
            ),
            cid: $data['cid'] ?? null,
            cursor: $data['cursor'] ?? null,
        );
    }
}
