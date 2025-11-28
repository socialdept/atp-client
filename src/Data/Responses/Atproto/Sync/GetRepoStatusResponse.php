<?php

namespace SocialDept\AtpClient\Data\Responses\Atproto\Sync;

use Illuminate\Contracts\Support\Arrayable;

/**
 * @implements Arrayable<string, mixed>
 */
class GetRepoStatusResponse implements Arrayable
{
    public function __construct(
        public readonly string $did,
        public readonly bool $active,
        public readonly ?string $status = null,
        public readonly ?string $rev = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            did: $data['did'],
            active: $data['active'],
            status: $data['status'] ?? null,
            rev: $data['rev'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'did' => $this->did,
            'active' => $this->active,
            'status' => $this->status,
            'rev' => $this->rev,
        ];
    }
}
