<?php

namespace SocialDept\AtpClient\Data\Responses\Bsky\Feed;

use SocialDept\AtpSchema\Generated\App\Bsky\Actor\Defs\ProfileView;

class GetRepostedByResponse
{
    /**
     * @param  array<ProfileView>  $repostedBy
     */
    public function __construct(
        public readonly string $uri,
        public readonly array $repostedBy,
        public readonly ?string $cid = null,
        public readonly ?string $cursor = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            uri: $data['uri'],
            repostedBy: array_map(
                fn (array $profile) => ProfileView::fromArray($profile),
                $data['repostedBy'] ?? []
            ),
            cid: $data['cid'] ?? null,
            cursor: $data['cursor'] ?? null,
        );
    }
}
