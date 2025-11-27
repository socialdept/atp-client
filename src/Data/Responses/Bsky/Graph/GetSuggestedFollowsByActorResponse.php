<?php

namespace SocialDept\AtpClient\Data\Responses\Bsky\Graph;

use SocialDept\AtpSchema\Generated\App\Bsky\Actor\Defs\ProfileView;

class GetSuggestedFollowsByActorResponse
{
    /**
     * @param  array<ProfileView>  $suggestions
     */
    public function __construct(
        public readonly array $suggestions,
        public readonly ?bool $isFallback = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            suggestions: array_map(
                fn (array $profile) => ProfileView::fromArray($profile),
                $data['suggestions'] ?? []
            ),
            isFallback: $data['isFallback'] ?? null,
        );
    }
}
