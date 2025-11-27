<?php

namespace SocialDept\AtpClient\Data\Responses\Bsky\Feed;

use SocialDept\AtpSchema\Generated\App\Bsky\Feed\Defs\GeneratorView;

class GetFeedGeneratorResponse
{
    public function __construct(
        public readonly GeneratorView $view,
        public readonly bool $isOnline,
        public readonly bool $isValid,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            view: GeneratorView::fromArray($data['view']),
            isOnline: $data['isOnline'],
            isValid: $data['isValid'],
        );
    }
}
