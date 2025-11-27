<?php

namespace SocialDept\AtpClient\Data\Responses\Bsky\Graph;

use SocialDept\AtpSchema\Generated\App\Bsky\Actor\Defs\ProfileView;

class GetFollowsResponse
{
    /**
     * @param  array<ProfileView>  $follows
     */
    public function __construct(
        public readonly ProfileView $subject,
        public readonly array $follows,
        public readonly ?string $cursor = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            subject: ProfileView::fromArray($data['subject']),
            follows: array_map(
                fn (array $profile) => ProfileView::fromArray($profile),
                $data['follows'] ?? []
            ),
            cursor: $data['cursor'] ?? null,
        );
    }
}
