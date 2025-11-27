<?php

namespace SocialDept\AtpClient\Data\Responses\Ozone\Moderation;

use SocialDept\AtpSchema\Generated\Tools\Ozone\Moderation\Defs\RepoView;

class SearchReposResponse
{
    /**
     * @param  array<RepoView>  $repos
     */
    public function __construct(
        public readonly array $repos,
        public readonly ?string $cursor = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            repos: array_map(
                fn (array $repo) => RepoView::fromArray($repo),
                $data['repos'] ?? []
            ),
            cursor: $data['cursor'] ?? null,
        );
    }
}
