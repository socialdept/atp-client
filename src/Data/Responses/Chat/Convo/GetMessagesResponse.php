<?php

namespace SocialDept\AtpClient\Data\Responses\Chat\Convo;

use SocialDept\AtpSchema\Generated\Chat\Bsky\Convo\Defs\DeletedMessageView;
use SocialDept\AtpSchema\Generated\Chat\Bsky\Convo\Defs\MessageView;

class GetMessagesResponse
{
    /**
     * @param  array<MessageView|DeletedMessageView>  $messages
     */
    public function __construct(
        public readonly array $messages,
        public readonly ?string $cursor = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            messages: array_map(
                function (array $message) {
                    if (isset($message['$type']) && $message['$type'] === 'chat.bsky.convo.defs#deletedMessageView') {
                        return DeletedMessageView::fromArray($message);
                    }

                    return MessageView::fromArray($message);
                },
                $data['messages'] ?? []
            ),
            cursor: $data['cursor'] ?? null,
        );
    }
}
