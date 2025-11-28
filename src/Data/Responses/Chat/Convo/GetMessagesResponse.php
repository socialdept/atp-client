<?php

namespace SocialDept\AtpClient\Data\Responses\Chat\Convo;

use Illuminate\Support\Collection;
use SocialDept\AtpSchema\Generated\Chat\Bsky\Convo\Defs\DeletedMessageView;
use SocialDept\AtpSchema\Generated\Chat\Bsky\Convo\Defs\MessageView;

class GetMessagesResponse
{
    /**
     * @param  Collection<int, MessageView|DeletedMessageView>  $messages
     */
    public function __construct(
        public readonly Collection $messages,
        public readonly ?string $cursor = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            messages: collect($data['messages'] ?? [])->map(
                function (array $message) {
                    if (isset($message['$type']) && $message['$type'] === 'chat.bsky.convo.defs#deletedMessageView') {
                        return DeletedMessageView::fromArray($message);
                    }

                    return MessageView::fromArray($message);
                }
            ),
            cursor: $data['cursor'] ?? null,
        );
    }
}
