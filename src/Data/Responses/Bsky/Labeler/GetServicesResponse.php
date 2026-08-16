<?php

namespace SocialDept\AtpClient\Data\Responses\Bsky\Labeler;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use SocialDept\AtpSchema\Generated\App\Bsky\Labeler\Defs\LabelerView;
use SocialDept\AtpSchema\Generated\App\Bsky\Labeler\Defs\LabelerViewDetailed;

/**
 * @implements Arrayable<string, mixed>
 */
class GetServicesResponse implements Arrayable
{
    /**
     * @param  Collection<int, LabelerView|LabelerViewDetailed>  $views
     */
    public function __construct(
        public readonly Collection $views,
    ) {
    }

    public static function fromArray(array $data, bool $detailed = false): self
    {
        return new self(
            views: collect($data['views'] ?? [])->map(
                fn (array $view) => $detailed
                    ? LabelerViewDetailed::fromArray($view)
                    : LabelerView::fromArray($view)
            ),
        );
    }

    public function toArray(): array
    {
        return [
            'views' => $this->views->map(fn (LabelerView|LabelerViewDetailed $v) => $v->toArray())->all(),
        ];
    }
}
