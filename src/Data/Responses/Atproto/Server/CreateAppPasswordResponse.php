<?php

namespace SocialDept\AtpClient\Data\Responses\Atproto\Server;

use Illuminate\Contracts\Support\Arrayable;

/**
 * A new app password. The PDS returns the password only here, so keep it
 * now or create another.
 *
 * @implements Arrayable<string, mixed>
 */
class CreateAppPasswordResponse implements Arrayable
{
    public function __construct(
        public readonly string $name,
        public readonly string $password,
        public readonly string $createdAt,
        public readonly bool $privileged = false,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            password: $data['password'],
            createdAt: $data['createdAt'],
            privileged: (bool) ($data['privileged'] ?? false),
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'password' => $this->password,
            'createdAt' => $this->createdAt,
            'privileged' => $this->privileged,
        ];
    }
}
