<?php

namespace SocialDept\AtpClient\Data\Responses\Atproto\Sync;

class ListReposResponse
{
    /**
     * @param  array<array{did: string, head: string, rev: string, active?: bool, status?: string}>  $repos
     */
    public function __construct(
        public readonly array $repos,
        public readonly ?string $cursor = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            repos: $data['repos'] ?? [],
            cursor: $data['cursor'] ?? null,
        );
    }
}
