<?php

namespace SocialDept\AtpClient\Data\Responses\Atproto\Repo;

class DescribeRepoResponse
{
    /**
     * @param  array<string>  $collections
     */
    public function __construct(
        public readonly string $handle,
        public readonly string $did,
        public readonly mixed $didDoc,
        public readonly array $collections,
        public readonly bool $handleIsCorrect,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            handle: $data['handle'],
            did: $data['did'],
            didDoc: $data['didDoc'],
            collections: $data['collections'] ?? [],
            handleIsCorrect: $data['handleIsCorrect'],
        );
    }
}
