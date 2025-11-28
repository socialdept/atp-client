<?php

namespace SocialDept\AtpClient\Data\Responses\Bsky\Graph;

use Illuminate\Support\Collection;
use SocialDept\AtpSchema\Generated\App\Bsky\Graph\Defs\StarterPackViewBasic;

class GetStarterPacksResponse
{
    /**
     * @param  Collection<int, StarterPackViewBasic>  $starterPacks
     */
    public function __construct(
        public readonly Collection $starterPacks,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            starterPacks: collect($data['starterPacks'] ?? [])->map(
                fn (array $pack) => StarterPackViewBasic::fromArray($pack)
            ),
        );
    }
}
