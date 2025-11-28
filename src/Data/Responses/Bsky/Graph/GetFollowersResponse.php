<?php

namespace SocialDept\AtpClient\Data\Responses\Bsky\Graph;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use SocialDept\AtpSchema\Generated\App\Bsky\Actor\Defs\ProfileView;

/**
 * @implements Arrayable<string, mixed>
 */
class GetFollowersResponse implements Arrayable
{
    /**
     * @param  Collection<int, ProfileView>  $followers
     */
    public function __construct(
        public readonly ProfileView $subject,
        public readonly Collection $followers,
        public readonly ?string $cursor = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            subject: ProfileView::fromArray($data['subject']),
            followers: collect($data['followers'] ?? [])->map(
                fn (array $profile) => ProfileView::fromArray($profile)
            ),
            cursor: $data['cursor'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'subject' => $this->subject->toArray(),
            'followers' => $this->followers->map(fn (ProfileView $p) => $p->toArray())->all(),
            'cursor' => $this->cursor,
        ];
    }
}
