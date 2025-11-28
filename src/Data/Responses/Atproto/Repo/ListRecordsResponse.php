<?php

namespace SocialDept\AtpClient\Data\Responses\Atproto\Repo;

use Illuminate\Support\Collection;

class ListRecordsResponse
{
    /**
     * @param  Collection<int, array{uri: string, cid: string, value: mixed}>  $records
     */
    public function __construct(
        public readonly Collection $records,
        public readonly ?string $cursor = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            records: collect($data['records'] ?? []),
            cursor: $data['cursor'] ?? null,
        );
    }
}
