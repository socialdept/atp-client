<?php

namespace SocialDept\AtpClient\Builders;

use Closure;
use DateTimeInterface;
use SocialDept\AtpClient\Builders\Concerns\BuildsRichText;
use SocialDept\AtpClient\Builders\Embeds\ImagesBuilder;
use SocialDept\AtpClient\Client\Records\PostRecordClient;
use SocialDept\AtpClient\Contracts\Recordable;
use SocialDept\AtpClient\Data\StrongRef;
use SocialDept\AtpClient\Enums\Nsid\BskyFeed;

class PostBuilder implements Recordable
{
    use BuildsRichText;

    protected ?array $embed = null;

    protected ?array $reply = null;

    protected ?array $langs = null;

    protected ?DateTimeInterface $createdAt = null;

    protected ?PostRecordClient $client = null;

    /**
     * Create a new post builder instance
     */
    public static function make(): self
    {
        return new self();
    }

    /**
     * Add images embed
     *
     * @param  Closure|array  $images  Closure receiving ImagesBuilder, or array of image data
     */
    public function images(Closure|array $images): self
    {
        if ($images instanceof Closure) {
            $builder = ImagesBuilder::make();
            $images($builder);
            $this->embed = $builder->toArray();
        } else {
            $this->embed = [
                '$type' => 'app.bsky.embed.images',
                'images' => array_map(fn ($img) => $this->normalizeImageData($img), $images),
            ];
        }

        return $this;
    }

    /**
     * Add external link embed (link card)
     *
     * @param  string  $uri  URL of the external content
     * @param  string  $title  Title of the link card
     * @param  string  $description  Description text
     * @param  mixed|null  $thumb  Optional thumbnail blob
     */
    public function external(string $uri, string $title, string $description, mixed $thumb = null): self
    {
        $external = [
            'uri' => $uri,
            'title' => $title,
            'description' => $description,
        ];

        if ($thumb !== null) {
            $external['thumb'] = $this->normalizeBlob($thumb);
        }

        $this->embed = [
            '$type' => 'app.bsky.embed.external',
            'external' => $external,
        ];

        return $this;
    }

    /**
     * Add video embed
     *
     * @param  mixed  $blob  Video blob reference
     * @param  string|null  $alt  Alt text for the video
     * @param  array|null  $captions  Optional captions array
     */
    public function video(mixed $blob, ?string $alt = null, ?array $captions = null): self
    {
        $video = [
            '$type' => 'app.bsky.embed.video',
            'video' => $this->normalizeBlob($blob),
        ];

        if ($alt !== null) {
            $video['alt'] = $alt;
        }

        if ($captions !== null) {
            $video['captions'] = $captions;
        }

        $this->embed = $video;

        return $this;
    }

    /**
     * Add quote embed (embed another post)
     */
    public function quote(StrongRef $post): self
    {
        $this->embed = [
            '$type' => 'app.bsky.embed.record',
            'record' => $post->toArray(),
        ];

        return $this;
    }

    /**
     * Set as a reply to another post
     *
     * @param  StrongRef  $parent  The post being replied to
     * @param  StrongRef|null  $root  The root post of the thread (defaults to parent if not provided)
     */
    public function replyTo(StrongRef $parent, ?StrongRef $root = null): self
    {
        $this->reply = [
            'parent' => $parent->toArray(),
            'root' => ($root ?? $parent)->toArray(),
        ];

        return $this;
    }

    /**
     * Set the post languages
     *
     * @param  array  $langs  Array of BCP-47 language codes
     */
    public function langs(array $langs): self
    {
        $this->langs = $langs;

        return $this;
    }

    /**
     * Set the creation timestamp
     */
    public function createdAt(DateTimeInterface $date): self
    {
        $this->createdAt = $date;

        return $this;
    }

    /**
     * Bind to a PostRecordClient for creating the post
     */
    public function for(PostRecordClient $client): self
    {
        $this->client = $client;

        return $this;
    }

    /**
     * Create the post (requires client binding via for() or build())
     *
     * @throws \RuntimeException If no client is bound
     */
    public function create(): StrongRef
    {
        if ($this->client === null) {
            throw new \RuntimeException(
                'No client bound. Use ->for($client) or create via $client->bsky->post->build()'
            );
        }

        return $this->client->create($this);
    }

    /**
     * Convert to array for XRPC (implements Recordable)
     */
    public function toArray(): array
    {
        $record = $this->getTextAndFacets();

        if ($this->embed !== null) {
            $record['embed'] = $this->embed;
        }

        if ($this->reply !== null) {
            $record['reply'] = $this->reply;
        }

        if ($this->langs !== null) {
            $record['langs'] = $this->langs;
        }

        $record['createdAt'] = ($this->createdAt ?? now())->format('c');
        $record['$type'] = $this->getType();

        return $record;
    }

    /**
     * Get the record type (implements Recordable)
     */
    public function getType(): string
    {
        return BskyFeed::Post->value;
    }

    /**
     * Normalize image data from array format
     */
    protected function normalizeImageData(array $data): array
    {
        $image = [
            'image' => $this->normalizeBlob($data['blob'] ?? $data['image']),
            'alt' => $data['alt'] ?? '',
        ];

        if (isset($data['aspectRatio'])) {
            $ratio = $data['aspectRatio'];
            $image['aspectRatio'] = is_array($ratio) && isset($ratio['width'])
                ? $ratio
                : ['width' => $ratio[0], 'height' => $ratio[1]];
        }

        return $image;
    }

    /**
     * Normalize blob to array format
     */
    protected function normalizeBlob(mixed $blob): array
    {
        if (is_array($blob)) {
            return $blob;
        }

        if (method_exists($blob, 'toArray')) {
            return $blob->toArray();
        }

        return (array) $blob;
    }
}
