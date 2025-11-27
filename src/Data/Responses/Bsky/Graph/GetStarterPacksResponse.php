<?php

namespace SocialDept\AtpClient\Data\Responses\Bsky\Graph;

use SocialDept\AtpSchema\Generated\App\Bsky\Graph\Defs\StarterPackViewBasic;

class GetStarterPacksResponse
{
    /**
     * @param  array<StarterPackViewBasic>  $starterPacks
     */
    public function __construct(
        public readonly array $starterPacks,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            starterPacks: array_map(
                fn (array $pack) => StarterPackViewBasic::fromArray($pack),
                $data['starterPacks'] ?? []
            ),
        );
    }
}
