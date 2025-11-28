<?php

namespace SocialDept\AtpClient\Data\Responses\Bsky\Feed;

use Illuminate\Contracts\Support\Arrayable;
use SocialDept\AtpSchema\Generated\App\Bsky\Feed\Defs\ThreadViewPost;

/**
 * @implements Arrayable<string, mixed>
 */
class GetPostThreadResponse implements Arrayable
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

    public function toArray(): array
    {
        return [
            'thread' => $this->thread->toArray(),
            'threadgate' => $this->threadgate,
        ];
    }
}
