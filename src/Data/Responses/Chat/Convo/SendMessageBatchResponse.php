<?php

namespace SocialDept\AtpClient\Data\Responses\Chat\Convo;

use SocialDept\AtpSchema\Generated\Chat\Bsky\Convo\Defs\MessageView;

class SendMessageBatchResponse
{
    /**
     * @param  array<MessageView>  $items
     */
    public function __construct(
        public readonly array $items,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            items: array_map(
                fn (array $item) => MessageView::fromArray($item),
                $data['items'] ?? []
            ),
        );
    }
}
