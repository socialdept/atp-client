<?php

namespace SocialDept\AtpClient\Data\Responses\Atproto\Server;

use Illuminate\Contracts\Support\Arrayable;

/**
 * An app password as the PDS lists it. The password itself is only ever
 * returned once, when it is created.
 *
 * @implements Arrayable<string, mixed>
 */
class AppPassword implements Arrayable
{
    public function __construct(
        public readonly string $name,
        public readonly string $createdAt,
        public readonly bool $privileged = false,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            createdAt: $data['createdAt'],
            privileged: (bool) ($data['privileged'] ?? false),
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'createdAt' => $this->createdAt,
            'privileged' => $this->privileged,
        ];
    }
}
