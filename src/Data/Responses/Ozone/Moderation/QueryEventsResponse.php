<?php

namespace SocialDept\AtpClient\Data\Responses\Ozone\Moderation;

use SocialDept\AtpSchema\Generated\Tools\Ozone\Moderation\Defs\ModEventView;

class QueryEventsResponse
{
    /**
     * @param  array<ModEventView>  $events
     */
    public function __construct(
        public readonly array $events,
        public readonly ?string $cursor = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            events: array_map(
                fn (array $event) => ModEventView::fromArray($event),
                $data['events'] ?? []
            ),
            cursor: $data['cursor'] ?? null,
        );
    }
}
