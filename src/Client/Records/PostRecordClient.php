<?php

namespace SocialDept\AtpClient\Client\Records;

use DateTimeInterface;
use SocialDept\AtpClient\Attributes\ScopedEndpoint;
use SocialDept\AtpClient\Builders\PostBuilder;
use SocialDept\AtpClient\Client\Requests\Request;
use SocialDept\AtpClient\Contracts\Recordable;
use SocialDept\AtpClient\Data\StrongRef;
use SocialDept\AtpClient\Enums\Nsid\BskyFeed;
use SocialDept\AtpClient\Enums\Scope;
use SocialDept\AtpClient\RichText\TextBuilder;
use SocialDept\AtpSchema\Generated\App\Bsky\Feed\Defs\PostView;

class PostRecordClient extends Request
{
    /**
     * Create a new post builder bound to this client
     */
    public function build(): PostBuilder
    {
        return PostBuilder::make()->for($this);
    }

    /**
     * Create a post
     *
     * @requires transition:generic OR (rpc:com.atproto.repo.createRecord AND repo:app.bsky.feed.post?action=create)
     */
    #[ScopedEndpoint(Scope::TransitionGeneric, granular: 'rpc:com.atproto.repo.createRecord')]
    #[ScopedEndpoint(Scope::TransitionGeneric, granular: 'repo:app.bsky.feed.post?action=create')]
    public function create(
        string|array|Recordable $content,
        ?array $facets = null,
        ?array $embed = null,
        ?array $reply = null,
        ?array $langs = null,
        ?DateTimeInterface $createdAt = null
    ): StrongRef {
        // Handle different input types
        if (is_string($content)) {
            $record = [
                'text' => $content,
                'facets' => $facets ?? TextBuilder::parse($content)['facets'],
            ];
        } elseif ($content instanceof Recordable) {
            $record = $content->toArray();
        } else {
            $record = $content;
        }

        // Add optional fields (only for non-Recordable inputs)
        if (! ($content instanceof Recordable)) {
            if ($embed) {
                $record['embed'] = $embed;
            }
            if ($reply) {
                $record['reply'] = $reply;
            }
            if ($langs) {
                $record['langs'] = $langs;
            }
        }

        if (! isset($record['createdAt'])) {
            $record['createdAt'] = ($createdAt ?? now())->format('c');
        }

        // Ensure $type is set
        if (! isset($record['$type'])) {
            $record['$type'] = BskyFeed::Post->value;
        }

        $response = $this->atp->atproto->repo->createRecord(
            repo: $this->atp->client->session()->did(),
            collection: BskyFeed::Post,
            record: $record
        );

        return StrongRef::fromResponse($response->json());
    }

    /**
     * Update a post
     *
     * @requires transition:generic OR (rpc:com.atproto.repo.putRecord AND repo:app.bsky.feed.post?action=update)
     */
    #[ScopedEndpoint(Scope::TransitionGeneric, granular: 'rpc:com.atproto.repo.putRecord')]
    #[ScopedEndpoint(Scope::TransitionGeneric, granular: 'repo:app.bsky.feed.post?action=update')]
    public function update(string $rkey, array $record): StrongRef
    {
        // Ensure $type is set
        if (! isset($record['$type'])) {
            $record['$type'] = BskyFeed::Post->value;
        }

        $response = $this->atp->atproto->repo->putRecord(
            repo: $this->atp->client->session()->did(),
            collection: BskyFeed::Post,
            rkey: $rkey,
            record: $record
        );

        return StrongRef::fromResponse($response->toArray());
    }

    /**
     * Delete a post
     *
     * @requires transition:generic OR (rpc:com.atproto.repo.deleteRecord AND repo:app.bsky.feed.post?action=delete)
     */
    #[ScopedEndpoint(Scope::TransitionGeneric, granular: 'rpc:com.atproto.repo.deleteRecord')]
    #[ScopedEndpoint(Scope::TransitionGeneric, granular: 'repo:app.bsky.feed.post?action=delete')]
    public function delete(string $rkey): void
    {
        $this->atp->atproto->repo->deleteRecord(
            repo: $this->atp->client->session()->did(),
            collection: BskyFeed::Post,
            rkey: $rkey
        );
    }

    /**
     * Get a post
     *
     * @requires transition:generic (rpc:com.atproto.repo.getRecord)
     */
    #[ScopedEndpoint(Scope::TransitionGeneric, granular: 'rpc:com.atproto.repo.getRecord')]
    public function get(string $rkey, ?string $cid = null): PostView
    {
        $response = $this->atp->atproto->repo->getRecord(
            repo: $this->atp->client->session()->did(),
            collection: BskyFeed::Post,
            rkey: $rkey,
            cid: $cid
        );

        return PostView::fromArray($response->value);
    }

}
