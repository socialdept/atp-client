<?php

namespace SocialDept\AtpClient\Data\Responses\Chat\Convo;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use SocialDept\AtpSchema\Generated\Chat\Bsky\Convo\Defs\MessageView;

/**
 * @implements Arrayable<string, mixed>
 */
class SendMessageBatchResponse implements Arrayable
{
    /**
     * @param  Collection<int, MessageView>  $items
     */
    public function __construct(
        public readonly Collection $items,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            items: collect($data['items'] ?? [])->map(
                fn (array $item) => MessageView::fromArray($item)
            ),
        );
    }

    public function toArray(): array
    {
        return [
            'items' => $this->items->map(fn (MessageView $m) => $m->toArray())->all(),
        ];
    }
}
