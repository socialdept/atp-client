<?php

namespace SocialDept\AtpClient\Data\Responses\Atproto\Identity;

use Illuminate\Contracts\Support\Arrayable;

/**
 * @implements Arrayable<string, string>
 */
class ResolveHandleResponse implements Arrayable
{
    public function __construct(
        public readonly string $did,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            did: $data['did'],
        );
    }

    public function toArray(): array
    {
        return [
            'did' => $this->did,
        ];
    }
}
