<?php

namespace SocialDept\AtpClient\Data\Responses\Chat\Convo;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;

/**
 * @implements Arrayable<string, mixed>
 */
class GetLogResponse implements Arrayable
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

    public function toArray(): array
    {
        return [
            'logs' => $this->logs->all(),
            'cursor' => $this->cursor,
        ];
    }
}
