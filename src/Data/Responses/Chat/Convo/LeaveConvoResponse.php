<?php

namespace SocialDept\AtpClient\Data\Responses\Chat\Convo;

use Illuminate\Contracts\Support\Arrayable;

/**
 * @implements Arrayable<string, mixed>
 */
class LeaveConvoResponse implements Arrayable
{
    public function __construct(
        public readonly string $convoId,
        public readonly string $rev,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            convoId: $data['convoId'],
            rev: $data['rev'],
        );
    }

    public function toArray(): array
    {
        return [
            'convoId' => $this->convoId,
            'rev' => $this->rev,
        ];
    }
}
