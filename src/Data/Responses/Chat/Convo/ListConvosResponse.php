<?php

namespace SocialDept\AtpClient\Data\Responses\Chat\Convo;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use SocialDept\AtpSchema\Generated\Chat\Bsky\Convo\Defs\ConvoView;

/**
 * @implements Arrayable<string, mixed>
 */
class ListConvosResponse implements Arrayable
{
    /**
     * @param  Collection<int, ConvoView>  $convos
     */
    public function __construct(
        public readonly Collection $convos,
        public readonly ?string $cursor = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            convos: collect($data['convos'] ?? [])->map(
                fn (array $convo) => ConvoView::fromArray($convo)
            ),
            cursor: $data['cursor'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'convos' => $this->convos->map(fn (ConvoView $c) => $c->toArray())->all(),
            'cursor' => $this->cursor,
        ];
    }
}
