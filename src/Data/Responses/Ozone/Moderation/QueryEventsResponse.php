<?php

namespace SocialDept\AtpClient\Data\Responses\Ozone\Moderation;

use Illuminate\Support\Collection;
use SocialDept\AtpSchema\Generated\Tools\Ozone\Moderation\Defs\ModEventView;

class QueryEventsResponse
{
    /**
     * @param  Collection<int, ModEventView>  $events
     */
    public function __construct(
        public readonly Collection $events,
        public readonly ?string $cursor = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            events: collect($data['events'] ?? [])->map(
                fn (array $event) => ModEventView::fromArray($event)
            ),
            cursor: $data['cursor'] ?? null,
        );
    }
}
