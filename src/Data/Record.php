<?php

namespace SocialDept\AtpClient\Data;

use Illuminate\Contracts\Support\Arrayable;

/**
 * Generic wrapper for AT Protocol records.
 *
 * @template T
 * @implements Arrayable<string, mixed>
 */
class Record implements Arrayable
{
    /**
     * @param  T  $value
     */
    public function __construct(
        public readonly string $uri,
        public readonly string $cid,
        public readonly mixed $value,
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
            uri: $data['uri'],
            cid: $data['cid'],
            value: $transformer($data['value']),
        );
    }

    /**
     * Create without transforming value.
     */
    public static function fromArrayRaw(array $data): self
    {
        return new self(
            uri: $data['uri'],
            cid: $data['cid'],
            value: $data['value'],
        );
    }

    public function toArray(): array
    {
        return [
            'uri' => $this->uri,
            'cid' => $this->cid,
            'value' => $this->value instanceof Arrayable
                ? $this->value->toArray()
                : $this->value,
        ];
    }
}
