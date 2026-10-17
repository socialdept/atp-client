<?php

namespace SocialDept\AtpClient\Data\Responses\Atproto\Repo;

use Illuminate\Contracts\Support\Arrayable;
use SocialDept\AtpSchema\Generated\Com\Atproto\Repo\ApplyWrites\CreateResult;
use SocialDept\AtpSchema\Generated\Com\Atproto\Repo\ApplyWrites\DeleteResult;
use SocialDept\AtpSchema\Generated\Com\Atproto\Repo\ApplyWrites\UpdateResult;
use SocialDept\AtpSchema\Generated\Com\Atproto\Repo\Defs\CommitMeta;

/**
 * The commit an applyWrites call produced, and one result per write in the
 * order the writes were sent.
 *
 * @implements Arrayable<string, mixed>
 */
class ApplyWritesResponse implements Arrayable
{
    /**
     * @param  array<int, CreateResult|UpdateResult|DeleteResult>  $results
     */
    public function __construct(
        public readonly ?CommitMeta $commit = null,
        public readonly array $results = [],
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            commit: isset($data['commit']) ? CommitMeta::fromArray($data['commit']) : null,
            results: array_map(fn (array $result) => match ($result['$type'] ?? null) {
                'com.atproto.repo.applyWrites#createResult' => CreateResult::fromArray($result),
                'com.atproto.repo.applyWrites#updateResult' => UpdateResult::fromArray($result),
                default => DeleteResult::fromArray($result),
            }, $data['results'] ?? []),
        );
    }

    public function toArray(): array
    {
        return [
            'commit' => $this->commit?->toArray(),
            'results' => array_map(fn ($result) => ['$type' => $result::getLexicon()] + $result->toArray(), $this->results),
        ];
    }
}
