<?php

namespace SocialDept\AtpClient\Data\Responses\Atproto\Sync;

use Illuminate\Contracts\Support\Arrayable;

/**
 * @implements Arrayable<string, mixed>
 */
class ListBlobsResponse implements Arrayable
{
    /**
     * @param  array<string>  $cids
     */
    public function __construct(
        public readonly array $cids,
        public readonly ?string $cursor = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            cids: $data['cids'] ?? [],
            cursor: $data['cursor'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'cids' => $this->cids,
            'cursor' => $this->cursor,
        ];
    }
}
