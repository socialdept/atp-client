<?php

namespace SocialDept\AtpClient\Data;

/**
 * Collection wrapper for paginated AT Protocol records.
 *
 * @template T
 */
class RecordCollection
{
    /**
     * @param  array<Record<T>>  $records
     */
    public function __construct(
        public readonly array $records,
        public readonly ?string $cursor = null,
    ) {}

    /**
     * @template U
     * @param  array  $data
     * @param  callable(array): U  $transformer
     * @return self<U>
     */
    public static function fromArray(array $data, callable $transformer): self
    {
        return new self(
            records: array_map(
                fn (array $record) => Record::fromArray($record, $transformer),
                $data['records'] ?? []
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
            records: array_map(
                fn (array $record) => Record::fromArrayRaw($record),
                $data['records'] ?? []
            ),
            cursor: $data['cursor'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'records' => array_map(fn (Record $r) => $r->toArray(), $this->records),
            'cursor' => $this->cursor,
        ];
    }
}
