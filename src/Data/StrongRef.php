<?php

namespace SocialDept\AtpClient\Data;

use Illuminate\Contracts\Support\Arrayable;

/**
 * @implements Arrayable<string, string>
 */
class StrongRef implements Arrayable
{
    public function __construct(
        public readonly string $uri,
        public readonly string $cid,
    ) {
    }

    public static function fromResponse(array $data): self
    {
        return new self(
            uri: $data['uri'],
            cid: $data['cid'],
        );
    }

    public function toArray(): array
    {
        return [
            'uri' => $this->uri,
            'cid' => $this->cid,
        ];
    }
}
