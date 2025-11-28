<?php

namespace SocialDept\AtpClient\Data\Responses\Atproto\Repo;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;

/**
 * @implements Arrayable<string, mixed>
 */
class ListRecordsResponse implements Arrayable
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

    public function toArray(): array
    {
        return [
            'records' => $this->records->all(),
            'cursor' => $this->cursor,
        ];
    }
}
