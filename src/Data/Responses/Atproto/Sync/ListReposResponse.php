<?php

namespace SocialDept\AtpClient\Data\Responses\Atproto\Sync;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;

/**
 * @implements Arrayable<string, mixed>
 */
class ListReposResponse implements Arrayable
{
    /**
     * @param  Collection<int, array{did: string, head: string, rev: string, active?: bool, status?: string}>  $repos
     */
    public function __construct(
        public readonly Collection $repos,
        public readonly ?string $cursor = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            repos: collect($data['repos'] ?? []),
            cursor: $data['cursor'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'repos' => $this->repos->all(),
            'cursor' => $this->cursor,
        ];
    }
}
