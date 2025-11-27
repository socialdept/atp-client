<?php

namespace SocialDept\AtpClient\Data\Responses\Atproto\Sync;

class ListBlobsResponse
{
    /**
     * @param  array<string>  $cids
     */
    public function __construct(
        public readonly array $cids,
        public readonly ?string $cursor = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            cids: $data['cids'] ?? [],
            cursor: $data['cursor'] ?? null,
        );
    }
}
