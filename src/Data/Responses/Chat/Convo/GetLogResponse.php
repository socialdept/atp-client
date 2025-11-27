<?php

namespace SocialDept\AtpClient\Data\Responses\Chat\Convo;

class GetLogResponse
{
    /**
     * @param  array<mixed>  $logs  Array of log event objects (LogBeginConvo, LogCreateMessage, etc.)
     */
    public function __construct(
        public readonly array $logs,
        public readonly ?string $cursor = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            logs: $data['logs'] ?? [],
            cursor: $data['cursor'] ?? null,
        );
    }
}
