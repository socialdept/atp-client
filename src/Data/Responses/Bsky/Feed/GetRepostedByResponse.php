<?php

namespace SocialDept\AtpClient\Data\Responses\Bsky\Feed;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use SocialDept\AtpSchema\Generated\App\Bsky\Actor\Defs\ProfileView;

/**
 * @implements Arrayable<string, mixed>
 */
class GetRepostedByResponse implements Arrayable
{
    /**
     * @param  Collection<int, ProfileView>  $repostedBy
     */
    public function __construct(
        public readonly string $uri,
        public readonly Collection $repostedBy,
        public readonly ?string $cid = null,
        public readonly ?string $cursor = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            uri: $data['uri'],
            repostedBy: collect($data['repostedBy'] ?? [])->map(
                fn (array $profile) => ProfileView::fromArray($profile)
            ),
            cid: $data['cid'] ?? null,
            cursor: $data['cursor'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'uri' => $this->uri,
            'repostedBy' => $this->repostedBy->map(fn (ProfileView $p) => $p->toArray())->all(),
            'cid' => $this->cid,
            'cursor' => $this->cursor,
        ];
    }
}
