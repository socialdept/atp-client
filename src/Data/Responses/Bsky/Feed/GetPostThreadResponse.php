<?php

namespace SocialDept\AtpClient\Data\Responses\Bsky\Feed;

use SocialDept\AtpSchema\Generated\App\Bsky\Feed\Defs\ThreadViewPost;

class GetPostThreadResponse
{
    public function __construct(
        public readonly ThreadViewPost $thread,
        public readonly mixed $threadgate = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            thread: ThreadViewPost::fromArray($data['thread']),
            threadgate: $data['threadgate'] ?? null,
        );
    }
}
