<?php

namespace SocialDept\AtpClient\Data\Responses\Bsky\Graph;

use SocialDept\AtpSchema\Generated\App\Bsky\Actor\Defs\ProfileView;

class GetKnownFollowersResponse
{
    /**
     * @param  array<ProfileView>  $followers
     */
    public function __construct(
        public readonly ProfileView $subject,
        public readonly array $followers,
        public readonly ?string $cursor = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            subject: ProfileView::fromArray($data['subject']),
            followers: array_map(
                fn (array $profile) => ProfileView::fromArray($profile),
                $data['followers'] ?? []
            ),
            cursor: $data['cursor'] ?? null,
        );
    }
}
