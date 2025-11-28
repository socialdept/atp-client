<?php

namespace SocialDept\AtpClient\Data\Responses\Atproto\Repo;

use Illuminate\Contracts\Support\Arrayable;

/**
 * @implements Arrayable<string, mixed>
 */
class DescribeRepoResponse implements Arrayable
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

    public function toArray(): array
    {
        return [
            'handle' => $this->handle,
            'did' => $this->did,
            'didDoc' => $this->didDoc,
            'collections' => $this->collections,
            'handleIsCorrect' => $this->handleIsCorrect,
        ];
    }
}
