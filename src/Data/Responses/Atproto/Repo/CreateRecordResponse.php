<?php

namespace SocialDept\AtpClient\Data\Responses\Atproto\Repo;

use Illuminate\Contracts\Support\Arrayable;
use SocialDept\AtpSchema\Generated\Com\Atproto\Repo\Defs\CommitMeta;

/**
 * @implements Arrayable<string, mixed>
 */
class CreateRecordResponse implements Arrayable
{
    public function __construct(
        public readonly string $uri,
        public readonly string $cid,
        public readonly ?CommitMeta $commit = null,
        public readonly ?string $validationStatus = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            uri: $data['uri'],
            cid: $data['cid'],
            commit: isset($data['commit']) ? CommitMeta::fromArray($data['commit']) : null,
            validationStatus: $data['validationStatus'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'uri' => $this->uri,
            'cid' => $this->cid,
            'commit' => $this->commit?->toArray(),
            'validationStatus' => $this->validationStatus,
        ];
    }
}
