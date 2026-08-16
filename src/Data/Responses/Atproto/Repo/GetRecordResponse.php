<?php

namespace SocialDept\AtpClient\Data\Responses\Atproto\Repo;

use Illuminate\Contracts\Support\Arrayable;

/**
 * @implements Arrayable<string, mixed>
 */
class GetRecordResponse implements Arrayable
{
    public function __construct(
        public readonly string $uri,
        public readonly mixed $value,
        public readonly ?string $cid = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            uri: $data['uri'],
            value: $data['value'],
            cid: $data['cid'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'uri' => $this->uri,
            'value' => $this->value,
            'cid' => $this->cid,
        ];
    }
}
