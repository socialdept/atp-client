<?php

namespace SocialDept\AtpClient\Data\Responses\Atproto\Repo;

class ListRecordsResponse
{
    /**
     * @param  array<array{uri: string, cid: string, value: mixed}>  $records
     */
    public function __construct(
        public readonly array $records,
        public readonly ?string $cursor = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            records: $data['records'] ?? [],
            cursor: $data['cursor'] ?? null,
        );
    }
}
