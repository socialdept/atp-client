<?php

namespace SocialDept\AtpClient\Client\Records;

use DateTimeInterface;
use SocialDept\AtpClient\Client\Requests\Request;
use SocialDept\AtpClient\Contracts\Recordable;
use SocialDept\AtpClient\Data\StrongRef;
use SocialDept\AtpClient\Http\Response;
use SocialDept\AtpClient\RichText\TextBuilder;

class PostRecordClient extends Request
{
    /**
     * Create a post
     */
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

        // Add optional fields
        if ($embed) {
            $record['embed'] = $embed;
        }
        if ($reply) {
            $record['reply'] = $reply;
        }
        if ($langs) {
            $record['langs'] = $langs;
        }
        if (! isset($record['createdAt'])) {
            $record['createdAt'] = ($createdAt ?? now())->format('c');
        }

        // Ensure $type is set
        if (! isset($record['$type'])) {
            $record['$type'] = 'app.bsky.feed.post';
        }

        // Create record via XRPC
        $response = $this->atp->client->post(
            endpoint: 'com.atproto.repo.createRecord',
            body: [
                'repo' => $this->atp->client->sessions->session($this->atp->client->identifier)->did(),
                'collection' => 'app.bsky.feed.post',
                'record' => $record,
            ]
        );

        return StrongRef::fromResponse($response->json());
    }

    /**
     * Update a post
     */
    public function update(string $rkey, array $record): StrongRef
    {
        // Ensure $type is set
        if (! isset($record['$type'])) {
            $record['$type'] = 'app.bsky.feed.post';
        }

        $response = $this->atp->client->post(
            endpoint: 'com.atproto.repo.putRecord',
            body: [
                'repo' => $this->atp->client->sessions->session($this->atp->client->identifier)->did(),
                'collection' => 'app.bsky.feed.post',
                'rkey' => $rkey,
                'record' => $record,
            ]
        );

        return StrongRef::fromResponse($response->json());
    }

    /**
     * Delete a post
     */
    public function delete(string $rkey): void
    {
        $this->atp->client->post(
            endpoint: 'com.atproto.repo.deleteRecord',
            body: [
                'repo' => $this->atp->client->sessions->session($this->atp->client->identifier)->did(),
                'collection' => 'app.bsky.feed.post',
                'rkey' => $rkey,
            ]
        );
    }

    /**
     * Get a post
     */
    public function get(string $rkey, ?string $cid = null): array
    {
        $response = $this->atp->client->get(
            endpoint: 'com.atproto.repo.getRecord',
            params: array_filter([
                'repo' => $this->atp->client->sessions->session($this->atp->client->identifier)->did(),
                'collection' => 'app.bsky.feed.post',
                'rkey' => $rkey,
                'cid' => $cid,
            ])
        );

        return $response->json('value');
    }

    /**
     * Create a reply to another post
     */
    public function reply(
        StrongRef $parent,
        StrongRef $root,
        string|array|Recordable $content,
        ?array $facets = null,
        ?array $embed = null,
        ?array $langs = null,
        ?DateTimeInterface $createdAt = null
    ): StrongRef {
        $reply = [
            'parent' => $parent->toArray(),
            'root' => $root->toArray(),
        ];

        return $this->create(
            content: $content,
            facets: $facets,
            embed: $embed,
            reply: $reply,
            langs: $langs,
            createdAt: $createdAt
        );
    }

    /**
     * Create a quote post (post with embedded post)
     */
    public function quote(
        StrongRef $quotedPost,
        string|array|Recordable $content,
        ?array $facets = null,
        ?array $langs = null,
        ?DateTimeInterface $createdAt = null
    ): StrongRef {
        $embed = [
            '$type' => 'app.bsky.embed.record',
            'record' => $quotedPost->toArray(),
        ];

        return $this->create(
            content: $content,
            facets: $facets,
            embed: $embed,
            langs: $langs,
            createdAt: $createdAt
        );
    }

    /**
     * Create a post with images
     */
    public function withImages(
        string|array|Recordable $content,
        array $images,
        ?array $facets = null,
        ?array $langs = null,
        ?DateTimeInterface $createdAt = null
    ): StrongRef {
        $embed = [
            '$type' => 'app.bsky.embed.images',
            'images' => $images,
        ];

        return $this->create(
            content: $content,
            facets: $facets,
            embed: $embed,
            langs: $langs,
            createdAt: $createdAt
        );
    }

    /**
     * Create a post with external link embed
     */
    public function withLink(
        string|array|Recordable $content,
        string $uri,
        string $title,
        string $description,
        ?string $thumbBlob = null,
        ?array $facets = null,
        ?array $langs = null,
        ?DateTimeInterface $createdAt = null
    ): StrongRef {
        $external = [
            'uri' => $uri,
            'title' => $title,
            'description' => $description,
        ];

        if ($thumbBlob) {
            $external['thumb'] = $thumbBlob;
        }

        $embed = [
            '$type' => 'app.bsky.embed.external',
            'external' => $external,
        ];

        return $this->create(
            content: $content,
            facets: $facets,
            embed: $embed,
            langs: $langs,
            createdAt: $createdAt
        );
    }
}
