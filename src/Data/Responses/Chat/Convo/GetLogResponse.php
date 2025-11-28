<?php

namespace SocialDept\AtpClient\Data\Responses\Chat\Convo;

use Illuminate\Support\Collection;

class GetLogResponse
{
    /**
     * @param  Collection<int, mixed>  $logs  Collection of log event objects (LogBeginConvo, LogCreateMessage, etc.)
     */
    public function __construct(
        public readonly Collection $logs,
        public readonly ?string $cursor = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            logs: collect($data['logs'] ?? []),
            cursor: $data['cursor'] ?? null,
        );
    }
}
