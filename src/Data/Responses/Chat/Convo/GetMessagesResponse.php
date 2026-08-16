<?php

namespace SocialDept\AtpClient\Data\Responses\Chat\Convo;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use SocialDept\AtpSchema\Generated\Chat\Bsky\Convo\Defs\DeletedMessageView;
use SocialDept\AtpSchema\Generated\Chat\Bsky\Convo\Defs\MessageView;

/**
 * @implements Arrayable<string, mixed>
 */
class GetMessagesResponse implements Arrayable
{
    /**
     * @param  Collection<int, MessageView|DeletedMessageView>  $messages
     */
    public function __construct(
        public readonly Collection $messages,
        public readonly ?string $cursor = null,
    ) {
    }

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

    public function toArray(): array
    {
        return [
            'messages' => $this->messages->map(fn (MessageView|DeletedMessageView $m) => $m->toArray())->all(),
            'cursor' => $this->cursor,
        ];
    }
}
