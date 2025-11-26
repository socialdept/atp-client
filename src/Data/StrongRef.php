<?php

namespace SocialDept\AtpClient\Data;

class StrongRef
{
    public function __construct(
        public readonly string $uri,
        public readonly string $cid,
    ) {}

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
