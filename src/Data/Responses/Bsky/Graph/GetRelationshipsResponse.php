<?php

namespace SocialDept\AtpClient\Data\Responses\Bsky\Graph;

class GetRelationshipsResponse
{
    /**
     * @param  array<mixed>  $relationships  Array of Relationship or NotFoundActor objects
     */
    public function __construct(
        public readonly array $relationships,
        public readonly ?string $actor = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            relationships: $data['relationships'] ?? [],
            actor: $data['actor'] ?? null,
        );
    }
}
