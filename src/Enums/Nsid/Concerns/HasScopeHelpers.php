<?php

namespace SocialDept\AtpClient\Enums\Nsid\Concerns;

trait HasScopeHelpers
{
    /**
     * Get the RPC scope format for this NSID.
     *
     * @example BskyActor::GetProfile->rpc() // "rpc:app.bsky.actor.getProfile"
     */
    public function rpc(): string
    {
        return 'rpc:' . $this->value;
    }

    /**
     * Get the repo scope format for this NSID.
     *
     * @example BskyGraph::Follow->repo(['create']) // "repo:app.bsky.graph.follow?action=create"
     * @example BskyFeed::Post->repo(['create', 'delete']) // "repo:app.bsky.feed.post?action=create&action=delete"
     * @example BskyFeed::Post->repo() // "repo:app.bsky.feed.post"
     */
    public function repo(array $actions = []): string
    {
        $scope = 'repo:' . $this->value;

        if (! empty($actions)) {
            $scope .= '?' . implode('&', array_map(fn ($action) => "action={$action}", $actions));
        }

        return $scope;
    }
}
