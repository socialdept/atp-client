<?php

namespace SocialDept\AtpClient\Data\Responses\Atproto\Server;

use Illuminate\Contracts\Support\Arrayable;

/**
 * @implements Arrayable<string, mixed>
 */
class ListAppPasswordsResponse implements Arrayable
{
    /**
     * @param  list<AppPassword>  $passwords
     */
    public function __construct(
        public readonly array $passwords,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            passwords: array_values(array_map(
                fn (array $password) => AppPassword::fromArray($password),
                $data['passwords'] ?? [],
            )),
        );
    }

    public function toArray(): array
    {
        return [
            'passwords' => array_map(fn (AppPassword $password) => $password->toArray(), $this->passwords),
        ];
    }
}
