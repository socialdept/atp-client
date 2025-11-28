<?php

namespace SocialDept\AtpClient\Data\Responses\Ozone\Moderation;

use Illuminate\Support\Collection;
use SocialDept\AtpSchema\Generated\Tools\Ozone\Moderation\Defs\SubjectStatusView;

class QueryStatusesResponse
{
    /**
     * @param  Collection<int, SubjectStatusView>  $subjectStatuses
     */
    public function __construct(
        public readonly Collection $subjectStatuses,
        public readonly ?string $cursor = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            subjectStatuses: collect($data['subjectStatuses'] ?? [])->map(
                fn (array $status) => SubjectStatusView::fromArray($status)
            ),
            cursor: $data['cursor'] ?? null,
        );
    }
}
