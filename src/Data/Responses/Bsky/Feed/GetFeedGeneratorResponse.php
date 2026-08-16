<?php

namespace SocialDept\AtpClient\Data\Responses\Bsky\Feed;

use Illuminate\Contracts\Support\Arrayable;
use SocialDept\AtpSchema\Generated\App\Bsky\Feed\Defs\GeneratorView;

/**
 * @implements Arrayable<string, mixed>
 */
class GetFeedGeneratorResponse implements Arrayable
{
    public function __construct(
        public readonly GeneratorView $view,
        public readonly bool $isOnline,
        public readonly bool $isValid,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            view: GeneratorView::fromArray($data['view']),
            isOnline: $data['isOnline'],
            isValid: $data['isValid'],
        );
    }

    public function toArray(): array
    {
        return [
            'view' => $this->view->toArray(),
            'isOnline' => $this->isOnline,
            'isValid' => $this->isValid,
        ];
    }
}
