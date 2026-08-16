<?php

namespace SocialDept\AtpClient\Data\Responses\Bsky\Graph;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use SocialDept\AtpSchema\Generated\App\Bsky\Graph\Defs\StarterPackViewBasic;

/**
 * @implements Arrayable<string, mixed>
 */
class GetStarterPacksResponse implements Arrayable
{
    /**
     * @param  Collection<int, StarterPackViewBasic>  $starterPacks
     */
    public function __construct(
        public readonly Collection $starterPacks,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            starterPacks: collect($data['starterPacks'] ?? [])->map(
                fn (array $pack) => StarterPackViewBasic::fromArray($pack)
            ),
        );
    }

    public function toArray(): array
    {
        return [
            'starterPacks' => $this->starterPacks->map(fn (StarterPackViewBasic $p) => $p->toArray())->all(),
        ];
    }
}
