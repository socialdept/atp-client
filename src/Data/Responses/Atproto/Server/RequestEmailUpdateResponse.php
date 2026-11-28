<?php

namespace SocialDept\AtpClient\Data\Responses\Atproto\Server;

use Illuminate\Contracts\Support\Arrayable;

/**
 * Whether updateEmail needs the code the PDS just emailed. It does once the
 * current address is confirmed.
 *
 * @implements Arrayable<string, mixed>
 */
class RequestEmailUpdateResponse implements Arrayable
{
    public function __construct(
        public readonly bool $tokenRequired,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            tokenRequired: (bool) ($data['tokenRequired'] ?? false),
        );
    }

    public function toArray(): array
    {
        return [
            'tokenRequired' => $this->tokenRequired,
        ];
    }
}
