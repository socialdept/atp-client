<?php

namespace SocialDept\AtpClient\Data\Responses\Chat\Convo;

class LeaveConvoResponse
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
}
