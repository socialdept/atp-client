<?php

namespace SocialDept\AtpClient\Data\Responses\Bsky\Actor;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use SocialDept\AtpSchema\Generated\App\Bsky\Actor\Defs\ProfileViewBasic;

/**
 * @implements Arrayable<string, mixed>
 */
class SearchActorsTypeaheadResponse implements Arrayable
{
    /**
     * @param  Collection<int, ProfileViewBasic>  $actors
     */
    public function __construct(
        public readonly Collection $actors,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            actors: collect($data['actors'] ?? [])->map(
                fn (array $actor) => ProfileViewBasic::fromArray($actor)
            ),
        );
    }

    public function toArray(): array
    {
        return [
            'actors' => $this->actors->map(fn (ProfileViewBasic $a) => $a->toArray())->all(),
        ];
    }
}
