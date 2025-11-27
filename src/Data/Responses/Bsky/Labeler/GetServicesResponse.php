<?php

namespace SocialDept\AtpClient\Data\Responses\Bsky\Labeler;

use SocialDept\AtpSchema\Generated\App\Bsky\Labeler\Defs\LabelerView;
use SocialDept\AtpSchema\Generated\App\Bsky\Labeler\Defs\LabelerViewDetailed;

class GetServicesResponse
{
    /**
     * @param  array<LabelerView|LabelerViewDetailed>  $views
     */
    public function __construct(
        public readonly array $views,
    ) {}

    public static function fromArray(array $data, bool $detailed = false): self
    {
        return new self(
            views: array_map(
                fn (array $view) => $detailed
                    ? LabelerViewDetailed::fromArray($view)
                    : LabelerView::fromArray($view),
                $data['views'] ?? []
            ),
        );
    }
}
