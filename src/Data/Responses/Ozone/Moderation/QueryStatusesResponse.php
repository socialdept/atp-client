<?php

namespace SocialDept\AtpClient\Data\Responses\Ozone\Moderation;

use SocialDept\AtpSchema\Generated\Tools\Ozone\Moderation\Defs\SubjectStatusView;

class QueryStatusesResponse
{
    /**
     * @param  array<SubjectStatusView>  $subjectStatuses
     */
    public function __construct(
        public readonly array $subjectStatuses,
        public readonly ?string $cursor = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            subjectStatuses: array_map(
                fn (array $status) => SubjectStatusView::fromArray($status),
                $data['subjectStatuses'] ?? []
            ),
            cursor: $data['cursor'] ?? null,
        );
    }
}
