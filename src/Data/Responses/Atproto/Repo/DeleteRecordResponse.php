<?php

namespace SocialDept\AtpClient\Data\Responses\Atproto\Repo;

use Illuminate\Contracts\Support\Arrayable;
use SocialDept\AtpSchema\Generated\Com\Atproto\Repo\Defs\CommitMeta;

/**
 * @implements Arrayable<string, mixed>
 */
class DeleteRecordResponse implements Arrayable
{
    public function __construct(
        public readonly ?CommitMeta $commit = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            commit: isset($data['commit']) ? CommitMeta::fromArray($data['commit']) : null,
        );
    }

    public function toArray(): array
    {
        return [
            'commit' => $this->commit?->toArray(),
        ];
    }
}
