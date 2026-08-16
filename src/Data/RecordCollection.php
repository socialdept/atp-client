<?php

namespace SocialDept\AtpClient\Data;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;

/**
 * Collection wrapper for paginated AT Protocol records.
 *
 * @template T
 * @implements Arrayable<string, mixed>
 */
class RecordCollection implements Arrayable
{
    /**
     * @param  Collection<int, Record<T>>  $records
     */
    public function __construct(
        public readonly Collection $records,
        public readonly ?string $cursor = null,
    ) {
    }

    /**
     * @template U
     * @param  array  $data
     * @param  callable(array): U  $transformer
     * @return self<U>
     */
    public static function fromArray(array $data, callable $transformer): self
    {
        return new self(
            records: collect($data['records'] ?? [])->map(
                fn (array $record) => Record::fromArray($record, $transformer)
            ),
            cursor: $data['cursor'] ?? null,
        );
    }

    /**
     * Create without transforming values.
     */
    public static function fromArrayRaw(array $data): self
    {
        return new self(
            records: collect($data['records'] ?? [])->map(
                fn (array $record) => Record::fromArrayRaw($record)
            ),
            cursor: $data['cursor'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'records' => $this->records->map(fn (Record $r) => $r->toArray())->all(),
            'cursor' => $this->cursor,
        ];
    }
}
