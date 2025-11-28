<?php

namespace SocialDept\AtpClient\Data\Responses\Bsky\Graph;

use Illuminate\Support\Collection;
use SocialDept\AtpSchema\Generated\App\Bsky\Actor\Defs\ProfileView;

class GetSuggestedFollowsByActorResponse
{
    /**
     * @param  Collection<int, ProfileView>  $suggestions
     */
    public function __construct(
        public readonly Collection $suggestions,
        public readonly ?bool $isFallback = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            suggestions: collect($data['suggestions'] ?? [])->map(
                fn (array $profile) => ProfileView::fromArray($profile)
            ),
            isFallback: $data['isFallback'] ?? null,
        );
    }
}
