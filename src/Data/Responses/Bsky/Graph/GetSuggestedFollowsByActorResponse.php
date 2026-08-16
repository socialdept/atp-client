<?php

namespace SocialDept\AtpClient\Data\Responses\Bsky\Graph;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use SocialDept\AtpSchema\Generated\App\Bsky\Actor\Defs\ProfileView;

/**
 * @implements Arrayable<string, mixed>
 */
class GetSuggestedFollowsByActorResponse implements Arrayable
{
    /**
     * @param  Collection<int, ProfileView>  $suggestions
     */
    public function __construct(
        public readonly Collection $suggestions,
        public readonly ?bool $isFallback = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            suggestions: collect($data['suggestions'] ?? [])->map(
                fn (array $profile) => ProfileView::fromArray($profile)
            ),
            isFallback: $data['isFallback'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'suggestions' => $this->suggestions->map(fn (ProfileView $p) => $p->toArray())->all(),
            'isFallback' => $this->isFallback,
        ];
    }
}
