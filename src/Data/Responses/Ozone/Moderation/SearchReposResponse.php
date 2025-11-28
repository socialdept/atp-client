<?php

namespace SocialDept\AtpClient\Data\Responses\Ozone\Moderation;

use Illuminate\Support\Collection;
use SocialDept\AtpSchema\Generated\Tools\Ozone\Moderation\Defs\RepoView;

class SearchReposResponse
{
    /**
     * @param  Collection<int, RepoView>  $repos
     */
    public function __construct(
        public readonly Collection $repos,
        public readonly ?string $cursor = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            repos: collect($data['repos'] ?? [])->map(
                fn (array $repo) => RepoView::fromArray($repo)
            ),
            cursor: $data['cursor'] ?? null,
        );
    }
}
