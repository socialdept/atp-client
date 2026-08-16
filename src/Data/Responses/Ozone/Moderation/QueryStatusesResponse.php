<?php

namespace SocialDept\AtpClient\Data\Responses\Ozone\Moderation;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use SocialDept\AtpSchema\Generated\Tools\Ozone\Moderation\Defs\SubjectStatusView;

/**
 * @implements Arrayable<string, mixed>
 */
class QueryStatusesResponse implements Arrayable
{
    /**
     * @param  Collection<int, SubjectStatusView>  $subjectStatuses
     */
    public function __construct(
        public readonly Collection $subjectStatuses,
        public readonly ?string $cursor = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            subjectStatuses: collect($data['subjectStatuses'] ?? [])->map(
                fn (array $status) => SubjectStatusView::fromArray($status)
            ),
            cursor: $data['cursor'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'subjectStatuses' => $this->subjectStatuses->map(fn (SubjectStatusView $s) => $s->toArray())->all(),
            'cursor' => $this->cursor,
        ];
    }
}
