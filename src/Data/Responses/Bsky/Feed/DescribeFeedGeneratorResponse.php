<?php

namespace SocialDept\AtpClient\Data\Responses\Bsky\Feed;

class DescribeFeedGeneratorResponse
{
    /**
     * @param  array<array{uri: string}>  $feeds
     */
    public function __construct(
        public readonly string $did,
        public readonly array $feeds,
        public readonly ?array $links = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            did: $data['did'],
            feeds: $data['feeds'] ?? [],
            links: $data['links'] ?? null,
        );
    }
}
