<?php

namespace SocialDept\AtpClient\Data\Responses\Bsky\Graph;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use SocialDept\AtpSchema\Generated\App\Bsky\Actor\Defs\ProfileView;

/**
 * @implements Arrayable<string, mixed>
 */
class GetFollowsResponse implements Arrayable
{
    /**
     * @param  Collection<int, ProfileView>  $follows
     */
    public function __construct(
        public readonly ProfileView $subject,
        public readonly Collection $follows,
        public readonly ?string $cursor = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            subject: ProfileView::fromArray($data['subject']),
            follows: collect($data['follows'] ?? [])->map(
                fn (array $profile) => ProfileView::fromArray($profile)
            ),
            cursor: $data['cursor'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'subject' => $this->subject->toArray(),
            'follows' => $this->follows->map(fn (ProfileView $p) => $p->toArray())->all(),
            'cursor' => $this->cursor,
        ];
    }
}
