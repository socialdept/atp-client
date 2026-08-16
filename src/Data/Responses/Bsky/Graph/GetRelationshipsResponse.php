<?php

namespace SocialDept\AtpClient\Data\Responses\Bsky\Graph;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;

/**
 * @implements Arrayable<string, mixed>
 */
class GetRelationshipsResponse implements Arrayable
{
    /**
     * @param  Collection<int, mixed>  $relationships  Collection of Relationship or NotFoundActor objects
     */
    public function __construct(
        public readonly Collection $relationships,
        public readonly ?string $actor = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            relationships: collect($data['relationships'] ?? []),
            actor: $data['actor'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'relationships' => $this->relationships->all(),
            'actor' => $this->actor,
        ];
    }
}
